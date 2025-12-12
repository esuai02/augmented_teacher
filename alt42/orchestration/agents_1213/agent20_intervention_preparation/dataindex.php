<?php
/**
 * 데이터 매핑 분석 도구 - 모든 에이전트용 통합 페이지
 * data_mapping_analysis.php의 내용을 기반으로 모든 에이전트에 대해 동일한 분석 페이지 제공
 * 
 * @file dataindex.php
 * @location alt42/orchestration/agents/agent_orchestration/
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $OUTPUT;
require_login();

// xmldb_table 클래스 로드
if (isset($CFG) && isset($CFG->libdir)) {
    require_once($CFG->libdir.'/ddllib.php');
}

// 파라미터
// $agentid가 외부에서 설정되지 않은 경우에만 URL 파라미터에서 읽음
// 에이전트 ID 고정
$agentid = 'agent20_intervention_preparation';
if (false) { // 고정 ID 사용으로 비활성화
    $agentid = optional_param('agentid', 'agent01_onboarding', PARAM_TEXT);
}
$studentid = optional_param('studentid', 1603, PARAM_INT);

// 권한 체크
$isTeacher = has_capability('moodle/course:manageactivities', context_system::instance());

if (!$isTeacher) {
    $studentid = $USER->id;
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
$agentBasePath = __DIR__ . '/rules/';
if (false) { // 고정 경로 사용
    // 다른 경로 시도
    $agentBasePath = __DIR__ . '/../../' . $agentid . '/rules/';
}

if (false) { // 고정 경로 사용
    die('에이전트 경로를 찾을 수 없습니다: ' . htmlspecialchars($agentid));
}

// rules.yaml 파일 읽기
$rulesYamlPath = $agentBasePath . 'rules.yaml';
$rulesYamlContent = file_exists($rulesYamlPath) ? file_get_contents($rulesYamlPath) : '';

// rules.yaml에서 사용하는 필드 추출
$rulesFields = [];
if (!empty($rulesYamlContent)) {
    // field: 패턴으로 필드 추출 (따옴표 있음/없음 모두 매칭)
    // 1. field: "필드명" 형태 (따옴표 있음)
    preg_match_all('/field:\s*"([^"]+)"/', $rulesYamlContent, $matches1);
    // 2. field: 필드명 형태 (따옴표 없음, 단어 경계 확인)
    preg_match_all('/field:\s+([a-zA-Z_][a-zA-Z0-9_]*)/', $rulesYamlContent, $matches2);
    
    if (!empty($matches1[1])) {
        $rulesFields = array_merge($rulesFields, $matches1[1]);
    }
    if (!empty($matches2[1])) {
        $rulesFields = array_merge($rulesFields, $matches2[1]);
    }
    
    if (!empty($rulesFields)) {
        $rulesFields = array_unique($rulesFields);
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

// view_reports.php에서 사용하는 데이터 필드 추출 (에이전트별로 경로 다를 수 있음)
$viewReportsPath = __DIR__ . '/../../studenthome/contextual_agents/beforegoinghome/view_reports.php';
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



/**
 * 테이블명을 기반으로 인터페이스 링크 생성 함수
 * DB 적용이 되어 있는 필드들에 대해 데이터 입력 인터페이스 링크 제공
 */
function getInterfaceLinkForTable($tableName, $agentid = '', $fieldName = '') {
    if (empty($tableName)) {
        return '';
    }

    $baseOmniUrl = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/';

    // 테이블별 인터페이스 매핑
    $tableInterfaceMapping = [
        // === MATHKING DB 온보딩 테이블들 (mdl_alt42g_*) ===
        'alt42g_learning_progress' => $baseOmniUrl . 'student_onboarding.php',
        'alt42g_learning_style' => $baseOmniUrl . 'student_onboarding.php',
        'alt42g_learning_goals' => $baseOmniUrl . 'student_onboarding.php',
        'alt42g_additional_info' => $baseOmniUrl . 'student_onboarding.php',
        'alt42g_learning_method' => $baseOmniUrl . 'student_onboarding.php',
        'alt42g_onboarding_status' => $baseOmniUrl . 'student_onboarding.php',

        // === Agent03 목표 분석 테이블들 (alt42g_*) ===
        'alt42g_student_goals' => $baseOmniUrl . 'info_goal.php',
        'alt42g_goal_analysis' => $baseOmniUrl . 'info_goal.php',
        'alt42g_learning_sessions' => $baseOmniUrl . 'dashboard.php',
        'alt42g_pomodoro_sessions' => $baseOmniUrl . 'dashboard.php',
        'alt42g_curriculum_progress' => $baseOmniUrl . 'dashboard.php',
        'alt42g_completed_units' => $baseOmniUrl . 'dashboard.php',

        // === 온보딩 설문 테이블 (alt42o_*) ===
        'alt42o_onboarding' => $baseOmniUrl . 'student_onboarding.php',
        'alt42o_learning_assessment_results' => $baseOmniUrl . 'student_onboarding.php',

        // === 학생 프로필 테이블 ===
        'alt42_student_profiles' => $baseOmniUrl . 'student_onboarding.php',

        // === MBTI 테이블 ===
        'abessi_mbtilog' => $baseOmniUrl . 'student_onboarding.php',

        // === 하교 설문 테이블 (alt42_goinghome) ===
        // 하교 전 설문 인터페이스 - 학습 감정, 일일 계획 등
        'alt42_goinghome' => 'https://mathking.kr/moodle/local/augmented_teacher/studenthome/contextual_agents/beforegoinghome/view_reports.php',

        // === 사용자 테이블 ===
        'user' => $baseOmniUrl . 'student_onboarding.php',  // 기본 사용자 정보는 온보딩에서 표시

        // === 시험 시스템 테이블 ===
        'alt42t_exam_settings' => $baseOmniUrl . 'exam_system.php',
        'alt42t_exam_resources' => $baseOmniUrl . 'exam_system.php',
        'student_exam_settings' => $baseOmniUrl . 'exam_system.php',
        'alt42_exam_schedule' => $baseOmniUrl . 'exam_system.php',  // 시험 일정 테이블

        // === 스케줄 테이블 ===
        'abessi_schedule' => $baseOmniUrl . 'info_schedule.php',

        // === 출결 테이블 ===
        'abessi_attendance_record' => $baseOmniUrl . 'attendance_teacher.php',
        'abessi_attendance_log' => $baseOmniUrl . 'attendance_teacher.php',

        // === 목표 테이블 ===
        'abessi_today' => $baseOmniUrl . 'info_goal.php',

        // === 메시지/오답노트 테이블 ===
        'abessi_messages' => $baseOmniUrl . 'dashboard.php',  // 오답노트/메시지

        // === 대시보드/학습 관련 ===
        'abessi_missionlog' => $baseOmniUrl . 'dashboard.php',
        'abessi_tracking' => $baseOmniUrl . 'dashboard.php',
        'abessi_progress' => $baseOmniUrl . 'dashboard.php',
        'abessi_indicators' => $baseOmniUrl . 'dashboard.php',  // 포모도로 요약

        // === Agent04 학생 활동 테이블 ===
        'alt42_student_activity' => $baseOmniUrl . 'dashboard.php',  // 학생 활동 분석/취약점 분석

        // === Agent04 온톨로지 테이블 ===
        'alt42_ontology_instances' => $baseOmniUrl . 'dashboard.php',  // 온톨로지 인스턴스 (취약점 추론)

        // === Agent07 리포트 테이블 ===
        'local_aug_reports' => $baseOmniUrl . 'dashboard.php',  // 학습 리포트

        // === Agent10 커리큘럼 테이블 ===
        'abessi_curriculum' => $baseOmniUrl . 'dashboard.php',  // 커리큘럼/단원 정보

        // === 런타임 필드 (실제 DB 없음) ===
        '_runtime' => '',  // 런타임 생성 필드는 링크 없음
    ];

    // mdl_ 접두사 제거하여 매핑 검색
    $tableNameWithoutPrefix = preg_replace('/^mdl_/', '', $tableName);

    // 1. 먼저 정확한 테이블명으로 검색
    if (isset($tableInterfaceMapping[$tableNameWithoutPrefix])) {
        return $tableInterfaceMapping[$tableNameWithoutPrefix];
    }

    // 2. 원본 테이블명으로 검색
    if (isset($tableInterfaceMapping[$tableName])) {
        return $tableInterfaceMapping[$tableName];
    }

    // 3. 패턴 매칭 (alt42g_ 로 시작하는 테이블)
    if (strpos($tableNameWithoutPrefix, 'alt42g_') === 0 || strpos($tableName, 'alt42g_') !== false) {
        return $baseOmniUrl . 'student_onboarding.php';
    }

    // 4. 패턴 매칭 (alt42o_ 로 시작하는 테이블)
    if (strpos($tableNameWithoutPrefix, 'alt42o_') === 0 || strpos($tableName, 'alt42o_') !== false) {
        return $baseOmniUrl . 'student_onboarding.php';
    }

    // 5. 패턴 매칭 (abessi_ 로 시작하는 테이블)
    if (strpos($tableNameWithoutPrefix, 'abessi_') === 0 || strpos($tableName, 'abessi_') !== false) {
        return $baseOmniUrl . 'dashboard.php';
    }

    return '';
}

/**
 * 필드 상태 해석 함수 (Agent01 전용)
 * 모든 경우의 수에 대한 해석 제공
 */
function interpretFieldStatus($mapping, $agentid, $DB, $rulesYamlContent, $dataAccessContent, $viewReportsContent, $studentid = 0) {
    // Agent01에만 적용
    if ($agentid !== 'agent01_onboarding') {
        return '';
    }
    
    $fieldName = $mapping['field'];
    $statusValue = in_array($mapping['status'] ?? 'unknown', ['missing', 'orphan', 'unmapped', 'unknown']) ? '준비' : '동작';
    $dbApplied = $mapping['db_applied'] ?? false;
    $dbTable = $mapping['db_table'] ?? '';
    $inRulesYaml = $mapping['in_rules_yaml'] ?? false;
    $inDataAccess = $mapping['in_data_access'] ?? false;
    $inDB = $mapping['in_db'] ?? false;
    
    // 테이블 상태 판단: 비어있음, 테이블명 표시, 테이블명 없음
    $tableStatus = '테이블명 없음';
    if (!empty($dbTable)) {
        // 테이블이 실제로 존재하는지 확인
        $tableExists = false;
        try {
            $tableExists = $DB->get_manager()->table_exists(new xmldb_table($dbTable));
        } catch (Exception $e) {
            $tableExists = false;
        }
        if ($tableExists) {
            // 테이블에 해당 필드가 있는지 확인
            try {
                $columns = $DB->get_columns($dbTable);
                if (isset($columns[$fieldName])) {
                    // 테이블과 필드가 모두 존재하는 경우
                    // 실제 데이터 존재 여부 확인 (샘플 데이터 체크)
                    $hasData = false;
                    if ($studentid > 0) {
                        try {
                            $sampleData = $DB->get_record($dbTable, ['userid' => $studentid], $fieldName, IGNORE_MISSING);
                            if ($sampleData && isset($sampleData->$fieldName) && $sampleData->$fieldName !== null && $sampleData->$fieldName !== '') {
                                $hasData = true;
                            }
                        } catch (Exception $e) {
                            // 데이터 확인 실패는 무시
                        }
                    }
                    $tableStatus = $hasData ? '테이블명 표시' : '비어있음';
                } else {
                    $tableStatus = '테이블명 표시 (필드 없음)';
                }
            } catch (Exception $e) {
                $tableStatus = '테이블명 표시 (확인 불가)';
            }
        } else {
            $tableStatus = '테이블명 없음 (테이블 미존재)';
        }
    }
    
    // 코드에서 테이블 관련 명령어 존재 여부 확인
    $hasTableCommand = false;
    if (!empty($dbTable)) {
        if (!empty($dataAccessContent)) {
            $hasTableCommand = strpos($dataAccessContent, $dbTable) !== false || 
                              strpos($dataAccessContent, "'" . $dbTable . "'") !== false ||
                              strpos($dataAccessContent, '"' . $dbTable . '"') !== false;
        }
    }
    
    // 해석 생성
    $interpretation = [];
    $recommendations = [];
    
    // 케이스 1: 동작 + DB 적용 + 테이블명 표시 + Rules.yaml 적용
    if ($statusValue === '동작' && $dbApplied && $tableStatus === '테이블명 표시' && $inRulesYaml) {
        $interpretation[] = '✅ <strong>완전 매핑 상태</strong>: 모든 구성요소가 정상 작동 중';
        $interpretation[] = '• DB에 실제 데이터 존재';
        $interpretation[] = '• Rules.yaml에 정의됨';
        $interpretation[] = '• 테이블: ' . htmlspecialchars($dbTable);
        if (!$hasTableCommand) {
            $recommendations[] = '⚠️ data_access.php에 테이블 조회 코드 추가 권장';
        }
    }
    // 케이스 2: 동작 + DB 적용 + 테이블명 표시 + Rules.yaml 미적용
    elseif ($statusValue === '동작' && $dbApplied && $tableStatus === '테이블명 표시' && !$inRulesYaml) {
        $interpretation[] = '⚠️ <strong>부분 매핑 상태</strong>: DB는 작동하지만 Rules.yaml 미정의';
        $interpretation[] = '• DB에 실제 데이터 존재';
        $interpretation[] = '• 테이블: ' . htmlspecialchars($dbTable);
        $recommendations[] = '📝 Rules.yaml에 필드 정의 추가 필요';
    }
    // 케이스 3: 동작 + DB 적용 + 테이블명 없음 + Rules.yaml 적용
    elseif ($statusValue === '동작' && $dbApplied && $tableStatus === '테이블명 없음' && $inRulesYaml) {
        $interpretation[] = '⚠️ <strong>데이터 불일치</strong>: Rules.yaml은 있지만 테이블명 미확인';
        $interpretation[] = '• Rules.yaml에 정의됨';
        $recommendations[] = '🔍 테이블 확인 로직 추가 필요 (dataindex.php의 $dbDataExists 생성 부분)';
        $recommendations[] = '📋 실제 DB 테이블 확인 필요';
    }
    // 케이스 4: 동작 + DB 적용 + 테이블명 없음 + Rules.yaml 미적용
    elseif ($statusValue === '동작' && $dbApplied && $tableStatus === '테이블명 없음' && !$inRulesYaml) {
        $interpretation[] = '⚠️ <strong>불완전 상태</strong>: DB 적용은 되지만 매핑 불완전';
        $recommendations[] = '📝 Rules.yaml에 필드 정의 추가 필요';
        $recommendations[] = '🔍 테이블 확인 로직 추가 필요';
    }
    // 케이스 5: 동작 + DB 미적용 + 테이블명 표시 + Rules.yaml 적용
    elseif ($statusValue === '동작' && !$dbApplied && $tableStatus === '테이블명 표시' && $inRulesYaml) {
        $interpretation[] = '⚠️ <strong>메타데이터 불일치</strong>: 테이블은 있지만 DB 적용 플래그가 false';
        $interpretation[] = '• Rules.yaml에 정의됨';
        $interpretation[] = '• 테이블: ' . htmlspecialchars($dbTable);
        $recommendations[] = '🔧 identifyDataType() 함수에서 db_applied 플래그 수정 필요';
    }
    // 케이스 6: 동작 + DB 미적용 + 테이블명 표시 + Rules.yaml 미적용
    elseif ($statusValue === '동작' && !$dbApplied && $tableStatus === '테이블명 표시' && !$inRulesYaml) {
        $interpretation[] = '⚠️ <strong>부분 작동</strong>: 테이블은 있지만 매핑 불완전';
        $interpretation[] = '• 테이블: ' . htmlspecialchars($dbTable);
        $recommendations[] = '📝 Rules.yaml에 필드 정의 추가 필요';
        $recommendations[] = '🔧 identifyDataType() 함수에서 db_applied 플래그 수정 필요';
    }
    // 케이스 7: 동작 + DB 미적용 + 테이블명 없음 + Rules.yaml 적용
    elseif ($statusValue === '동작' && !$dbApplied && $tableStatus === '테이블명 없음' && $inRulesYaml) {
        $interpretation[] = '⚠️ <strong>설정만 존재</strong>: Rules.yaml은 있지만 DB 연결 없음';
        $interpretation[] = '• Rules.yaml에 정의됨';
        $recommendations[] = '📋 실제 DB 테이블 생성 필요';
        $recommendations[] = '🔍 테이블 확인 로직 추가 필요';
    }
    // 케이스 8: 동작 + DB 미적용 + 테이블명 없음 + Rules.yaml 미적용
    elseif ($statusValue === '동작' && !$dbApplied && $tableStatus === '테이블명 없음' && !$inRulesYaml) {
        $interpretation[] = '❌ <strong>작동 불가</strong>: 모든 구성요소 누락';
        $recommendations[] = '📝 Rules.yaml에 필드 정의 추가 필요';
        $recommendations[] = '📋 실제 DB 테이블 생성 필요';
    }
    // 케이스 9: 준비 + DB 적용 + 테이블명 표시 + Rules.yaml 적용
    elseif ($statusValue === '준비' && $dbApplied && $tableStatus === '테이블명 표시' && $inRulesYaml) {
        $interpretation[] = '⏳ <strong>준비 완료</strong>: 모든 구성요소 준비됨, 동작 대기 중';
        $interpretation[] = '• Rules.yaml에 정의됨';
        $interpretation[] = '• 테이블: ' . htmlspecialchars($dbTable);
        $recommendations[] = '🚀 상태를 "동작"으로 변경하여 활성화 가능';
    }
    // 케이스 10: 준비 + DB 적용 + 테이블명 표시 + Rules.yaml 미적용
    elseif ($statusValue === '준비' && $dbApplied && $tableStatus === '테이블명 표시' && !$inRulesYaml) {
        $interpretation[] = '⏳ <strong>부분 준비</strong>: DB는 준비되었지만 Rules.yaml 미정의';
        $interpretation[] = '• 테이블: ' . htmlspecialchars($dbTable);
        $recommendations[] = '📝 Rules.yaml에 필드 정의 추가 필요';
    }
    // 케이스 11: 준비 + DB 적용 + 테이블명 없음 + Rules.yaml 적용
    elseif ($statusValue === '준비' && $dbApplied && $tableStatus === '테이블명 없음' && $inRulesYaml) {
        $interpretation[] = '⏳ <strong>설정 준비</strong>: Rules.yaml은 있지만 테이블명 미확인';
        $interpretation[] = '• Rules.yaml에 정의됨';
        $recommendations[] = '🔍 테이블 확인 로직 추가 필요';
    }
    // 케이스 12: 준비 + DB 적용 + 테이블명 없음 + Rules.yaml 미적용
    elseif ($statusValue === '준비' && $dbApplied && $tableStatus === '테이블명 없음' && !$inRulesYaml) {
        $interpretation[] = '⏳ <strong>불완전 준비</strong>: DB 적용은 되지만 매핑 불완전';
        $recommendations[] = '📝 Rules.yaml에 필드 정의 추가 필요';
        $recommendations[] = '🔍 테이블 확인 로직 추가 필요';
    }
    // 케이스 13: 준비 + DB 미적용 + 테이블명 표시 + Rules.yaml 적용
    elseif ($statusValue === '준비' && !$dbApplied && $tableStatus === '테이블명 표시' && $inRulesYaml) {
        $interpretation[] = '⏳ <strong>설정 준비</strong>: Rules.yaml과 테이블은 있지만 DB 적용 플래그 false';
        $interpretation[] = '• Rules.yaml에 정의됨';
        $interpretation[] = '• 테이블: ' . htmlspecialchars($dbTable);
        $recommendations[] = '🔧 identifyDataType() 함수에서 db_applied 플래그 수정 필요';
    }
    // 케이스 14: 준비 + DB 미적용 + 테이블명 표시 + Rules.yaml 미적용
    elseif ($statusValue === '준비' && !$dbApplied && $tableStatus === '테이블명 표시' && !$inRulesYaml) {
        $interpretation[] = '⏳ <strong>부분 준비</strong>: 테이블은 있지만 매핑 불완전';
        $interpretation[] = '• 테이블: ' . htmlspecialchars($dbTable);
        $recommendations[] = '📝 Rules.yaml에 필드 정의 추가 필요';
        $recommendations[] = '🔧 identifyDataType() 함수에서 db_applied 플래그 수정 필요';
    }
    // 케이스 15: 준비 + DB 미적용 + 테이블명 없음 + Rules.yaml 적용
    elseif ($statusValue === '준비' && !$dbApplied && $tableStatus === '테이블명 없음' && $inRulesYaml) {
        $interpretation[] = '⏳ <strong>최소 준비</strong>: Rules.yaml만 정의됨';
        $interpretation[] = '• Rules.yaml에 정의됨';
        $recommendations[] = '📋 실제 DB 테이블 생성 필요';
        $recommendations[] = '🔍 테이블 확인 로직 추가 필요';
    }
    // 케이스 16: 준비 + DB 미적용 + 테이블명 없음 + Rules.yaml 미적용
    elseif ($statusValue === '준비' && !$dbApplied && $tableStatus === '테이블명 없음' && !$inRulesYaml) {
        $interpretation[] = '❌ <strong>미준비</strong>: 모든 구성요소 누락';
        $recommendations[] = '📝 Rules.yaml에 필드 정의 추가 필요';
        $recommendations[] = '📋 실제 DB 테이블 생성 필요';
    }
    // 케이스 17-24: "비어있음" 케이스들
    // 케이스 17: 동작 + DB 적용 + 비어있음 + Rules.yaml 적용
    elseif ($statusValue === '동작' && $dbApplied && $tableStatus === '비어있음' && $inRulesYaml) {
        $interpretation[] = '⚠️ <strong>스키마만 존재</strong>: 테이블과 필드는 있지만 데이터 없음';
        $interpretation[] = '• Rules.yaml에 정의됨';
        $interpretation[] = '• 테이블: ' . htmlspecialchars($dbTable) . ' (데이터 없음)';
        $recommendations[] = '📊 실제 데이터 입력 필요';
    }
    // 케이스 18: 동작 + DB 적용 + 비어있음 + Rules.yaml 미적용
    elseif ($statusValue === '동작' && $dbApplied && $tableStatus === '비어있음' && !$inRulesYaml) {
        $interpretation[] = '⚠️ <strong>부분 스키마</strong>: 테이블은 있지만 Rules.yaml 미정의, 데이터 없음';
        $interpretation[] = '• 테이블: ' . htmlspecialchars($dbTable) . ' (데이터 없음)';
        $recommendations[] = '📝 Rules.yaml에 필드 정의 추가 필요';
        $recommendations[] = '📊 실제 데이터 입력 필요';
    }
    // 케이스 19: 동작 + DB 미적용 + 비어있음 + Rules.yaml 적용
    elseif ($statusValue === '동작' && !$dbApplied && $tableStatus === '비어있음' && $inRulesYaml) {
        $interpretation[] = '⚠️ <strong>설정만 존재</strong>: Rules.yaml은 있지만 DB 적용 플래그 false, 데이터 없음';
        $interpretation[] = '• Rules.yaml에 정의됨';
        $interpretation[] = '• 테이블: ' . htmlspecialchars($dbTable) . ' (데이터 없음)';
        $recommendations[] = '🔧 identifyDataType() 함수에서 db_applied 플래그 수정 필요';
        $recommendations[] = '📊 실제 데이터 입력 필요';
    }
    // 케이스 20: 동작 + DB 미적용 + 비어있음 + Rules.yaml 미적용
    elseif ($statusValue === '동작' && !$dbApplied && $tableStatus === '비어있음' && !$inRulesYaml) {
        $interpretation[] = '⚠️ <strong>불완전 스키마</strong>: 테이블은 있지만 매핑 불완전, 데이터 없음';
        $interpretation[] = '• 테이블: ' . htmlspecialchars($dbTable) . ' (데이터 없음)';
        $recommendations[] = '📝 Rules.yaml에 필드 정의 추가 필요';
        $recommendations[] = '🔧 identifyDataType() 함수에서 db_applied 플래그 수정 필요';
        $recommendations[] = '📊 실제 데이터 입력 필요';
    }
    // 케이스 21: 준비 + DB 적용 + 비어있음 + Rules.yaml 적용
    elseif ($statusValue === '준비' && $dbApplied && $tableStatus === '비어있음' && $inRulesYaml) {
        $interpretation[] = '⏳ <strong>스키마 준비 완료</strong>: 테이블과 필드는 있지만 데이터 없음';
        $interpretation[] = '• Rules.yaml에 정의됨';
        $interpretation[] = '• 테이블: ' . htmlspecialchars($dbTable) . ' (데이터 없음)';
        $recommendations[] = '📊 실제 데이터 입력 후 상태를 "동작"으로 변경 가능';
    }
    // 케이스 22: 준비 + DB 적용 + 비어있음 + Rules.yaml 미적용
    elseif ($statusValue === '준비' && $dbApplied && $tableStatus === '비어있음' && !$inRulesYaml) {
        $interpretation[] = '⏳ <strong>부분 스키마 준비</strong>: 테이블은 있지만 Rules.yaml 미정의, 데이터 없음';
        $interpretation[] = '• 테이블: ' . htmlspecialchars($dbTable) . ' (데이터 없음)';
        $recommendations[] = '📝 Rules.yaml에 필드 정의 추가 필요';
        $recommendations[] = '📊 실제 데이터 입력 필요';
    }
    // 케이스 23: 준비 + DB 미적용 + 비어있음 + Rules.yaml 적용
    elseif ($statusValue === '준비' && !$dbApplied && $tableStatus === '비어있음' && $inRulesYaml) {
        $interpretation[] = '⏳ <strong>설정 준비</strong>: Rules.yaml과 테이블은 있지만 DB 적용 플래그 false, 데이터 없음';
        $interpretation[] = '• Rules.yaml에 정의됨';
        $interpretation[] = '• 테이블: ' . htmlspecialchars($dbTable) . ' (데이터 없음)';
        $recommendations[] = '🔧 identifyDataType() 함수에서 db_applied 플래그 수정 필요';
        $recommendations[] = '📊 실제 데이터 입력 필요';
    }
    // 케이스 24: 준비 + DB 미적용 + 비어있음 + Rules.yaml 미적용
    elseif ($statusValue === '준비' && !$dbApplied && $tableStatus === '비어있음' && !$inRulesYaml) {
        $interpretation[] = '⏳ <strong>불완전 스키마 준비</strong>: 테이블은 있지만 매핑 불완전, 데이터 없음';
        $interpretation[] = '• 테이블: ' . htmlspecialchars($dbTable) . ' (데이터 없음)';
        $recommendations[] = '📝 Rules.yaml에 필드 정의 추가 필요';
        $recommendations[] = '🔧 identifyDataType() 함수에서 db_applied 플래그 수정 필요';
        $recommendations[] = '📊 실제 데이터 입력 필요';
    }
    
    // 추가 진단 정보
    if (!empty($dbTable)) {
        if (!$hasTableCommand && $inDataAccess) {
            $recommendations[] = '💡 data_access.php에 ' . htmlspecialchars($dbTable) . ' 테이블 조회 코드 추가 권장';
        }
    }
    
    // HTML 생성
    $html = '<div style="font-size: 0.8rem; line-height: 1.6;">';
    
    if (!empty($interpretation)) {
        $html .= '<div style="margin-bottom: 8px; padding: 8px; background: #f9fafb; border-radius: 6px; border-left: 3px solid #6366f1;">';
        foreach ($interpretation as $item) {
            $html .= '<div style="margin-bottom: 4px;">' . $item . '</div>';
        }
        $html .= '</div>';
    }
    
    if (!empty($recommendations)) {
        $html .= '<div style="padding: 8px; background: #fef3c7; border-radius: 6px; border-left: 3px solid #f59e0b;">';
        $html .= '<div style="font-weight: 600; margin-bottom: 4px; color: #92400e;">📋 개선 사항:</div>';
        foreach ($recommendations as $item) {
            $html .= '<div style="margin-bottom: 4px; color: #78350f;">' . $item . '</div>';
        }
        $html .= '</div>';
    }
    
    if (empty($interpretation) && empty($recommendations)) {
        $html .= '<span style="color: #9ca3af;">-</span>';
    }
    
    $html .= '</div>';
    
    return $html;
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

// 모든 필드 수집 (DB 필드 포함) - $dbDataExists 생성 전에 먼저 생성
$allFields = array_unique(array_merge($rulesFields, $dataAccessFields, $viewReportsFields));

// DB 필드에서 필드명 추출하여 추가 (스키마에 없지만 DB에 존재하는 필드 포함)
$dbFieldNames = [];
foreach ($dbFields as $dbField) {
    $dbFieldName = explode('.', $dbField)[1] ?? $dbField;
    if (!empty($dbFieldName) && !in_array($dbFieldName, $allFields)) {
        $dbFieldNames[] = $dbFieldName;
    }
}
$allFields = array_unique(array_merge($allFields, $dbFieldNames));
sort($allFields);

// 실제 DB 데이터 존재 여부 확인 (모든 필드 기준 - 스키마에 없는 DB 필드도 포함)
$dbDataExists = [];

// Agent01 Onboarding 필드별 명시적 테이블 매핑 (DATABASE_TABLE_MAPPING.md 기반)
$fieldTableMapping = [];

// agent20_intervention_preparation - 매핑 없음


foreach ($allFields as $field) {
    $foundTable = '';
    $sampleValue = null;

    // 1. 먼저 필드별 명시적 매핑 확인
    if (isset($fieldTableMapping[$field])) {
        $mappedTable = $fieldTableMapping[$field];

        // 테이블 존재 확인 (mdl_ 접두사 처리 포함)
        $tableExists = false;
        $actualTableName = $mappedTable;

        try {
            // Moodle의 table_exists()는 mdl_ 접두사 없이 테이블명을 받음
            // 먼저 mdl_ 접두사 제거 후 시도
            $tableNameWithoutPrefix = preg_replace('/^mdl_/', '', $mappedTable);
            $tableExists = $DB->get_manager()->table_exists(new xmldb_table($tableNameWithoutPrefix));

            if ($tableExists) {
                // 실제 DB 접근 시에는 접두사 없이 사용 (Moodle이 자동으로 접두사 추가)
                $actualTableName = $tableNameWithoutPrefix;
            }

            if (!$tableExists && strpos($mappedTable, 'mdl_') !== 0) {
                // mdl_ 접두사 없이 정의된 경우 그대로 시도
                if ($DB->get_manager()->table_exists(new xmldb_table($mappedTable))) {
                    $tableExists = true;
                    $actualTableName = $mappedTable;
                }
            }

            if ($tableExists) {
                // 계산 필드 특별 처리 (실제 DB 컬럼 없음, 다른 컬럼 기반 계산)
                $calculatedFields = [
                    'new_student_flag' => 'user',  // user.timecreated 기반 계산
                    'profile_update_flag' => 'alt42o_onboarding',  // updated_at > created_at 기반 계산
                    'hasUnitMastery' => 'alt42o_onboarding',  // math_unit_mastery의 온톨로지 매핑
                    'math_unit_mastery_formatted' => 'alt42o_onboarding',  // math_unit_mastery 포맷팅
                    'last_access' => 'user',  // user.lastaccess 필드
                ];

                // JSON 테이블 필드 (alt42_goinghome의 text 컬럼에 JSON으로 저장)
                $jsonTableFields = [
                    'boredom', 'calmness', 'concept_study', 'daily_plan', 'difficulty_level',
                    'easy_problems', 'error_note', 'forced_solving', 'inefficiency', 'intuition_solving',
                    'pace_anxiety', 'satisfaction', 'pomodoro',
                    // 추가 JSON 필드
                    'long_problem', 'missed_opportunity', 'positive_moment', 'problem_count',
                    'self_improvement', 'study_amount', 'unsaid_words', 'weekly_goal',
                    // agent02 전용 JSON 필드
                    'condition_score', 'disruption_cause', 'emotional_fluctuation_detected',
                    'emotional_response', 'helpful_encouragement', 'mood_change_habits',
                    'motivation_level', 'plan_disruption_flag', 'pride_moments',
                    'stress_level', 'study_mood', 'time_pressure_felt',
                    // agent05 설문 응답 필드 (alt42_goinghome JSON)
                    'concept_survey_response', 'type_survey_response', 'problem_survey_response',
                    'error_survey_response', 'qa_survey_response', 'review_survey_response',
                    'pomodoro_survey_response', 'home_check_survey_response', 'daily_learning_summary'
                ];

                // alt42_student_profiles JSON 필드
                $profileJsonFields = [
                    'anxious_units', 'areas_to_change', 'blocked_problem_types', 'confident_units',
                    'content_materials', 'content_usage_pattern', 'difficult_problem_types',
                    'favorite_subject', 'favorite_subject_reason', 'habits_to_keep',
                    'improvement_areas', 'memorable_parent_words', 'parent_concerns',
                    'parent_expectations', 'parent_message_emotion', 'parent_verification_needs',
                    'parent_consultation_completed', 'previous_study_methods',
                    'previous_study_methods.change', 'previous_study_methods.keep',
                    'spacing_review_schedule', 'time_consuming_units', 'unit_relations_db',
                    'well_solved_problem_types',
                    // agent05 페르소나/감정 프로필 필드 (alt42_student_profiles JSON)
                    'persona_type', 'persona_history_count', 'activity_persona_mapping',
                    'emotion_score', 'motivation_score', 'confidence_score', 'confidence_level'
                ];

                // 중첩 필드 (goals.long_term 등)
                $nestedFields = [
                    'goals.long_term', 'goals.mid_term', 'goals.short_term'
                ];

                // 런타임 필드 (실제 DB 없음, 코드에서 동적 생성)
                $runtimeFields = [
                    'learning_environment', 'questions_asked', 'rest_pattern', 'student_info',
                    'system_health_report', 'timestamp', 'user_message'
                ];

                // alt42o_onboarding 테이블의 온보딩 전용 필드들 (DB 컬럼으로 정의되어 있음)
                // 일부 필드는 스키마에 정의되어 있지만 실제 DB에 컬럼이 없을 수 있음
                $onboardingFields = [
                    'academy_grade' => 'alt42o_onboarding',       // 학원 등급/반
                    'academy_name' => 'alt42o_onboarding',        // 학원명
                    'academy_schedule' => 'alt42o_onboarding',    // 학원 수업 일정
                    'math_learning_style' => 'alt42o_onboarding', // 수학 학습 스타일
                    'math_recent_score' => 'alt42o_onboarding',   // 최근 수학 점수
                    'math_unit_mastery' => 'alt42o_onboarding',   // 단원별 마스터링 수준 (JSON)
                    'study_hours_per_week' => 'alt42o_onboarding', // 주당 학습 시간 (weekly_hours 매핑)
                    'study_style' => 'alt42o_onboarding',         // 학습 스타일
                    'textbooks' => 'alt42o_onboarding',           // 교재 목록
                ];

                if (isset($calculatedFields[$field])) {
                    // 계산 필드는 테이블에 컬럼이 없어도 테이블 매핑 유지
                    $foundTable = $actualTableName;
                    $sampleValue = '(계산 필드)';
                } elseif (in_array($field, $runtimeFields)) {
                    // 런타임 필드는 테이블 매핑 없이 표시
                    $foundTable = '_runtime';
                    $sampleValue = '(런타임 생성)';
                } elseif (in_array($field, $jsonTableFields) && $actualTableName === 'alt42_goinghome') {
                    // JSON 테이블 필드는 text 컬럼의 JSON에서 추출
                    $foundTable = $actualTableName;
                    try {
                        $jsonRecord = $DB->get_record_sql(
                            "SELECT text FROM {alt42_goinghome} WHERE userid = ? ORDER BY timecreated DESC LIMIT 1",
                            [$studentid],
                            IGNORE_MISSING
                        );
                        if ($jsonRecord && !empty($jsonRecord->text)) {
                            $jsonData = json_decode($jsonRecord->text, true);
                            if (is_array($jsonData) && isset($jsonData[$field])) {
                                $sampleValue = is_string($jsonData[$field]) ? substr($jsonData[$field], 0, 50) : $jsonData[$field];
                            } else {
                                $sampleValue = '(JSON 필드)';
                            }
                        } else {
                            $sampleValue = '(JSON 필드)';
                        }
                    } catch (Exception $e) {
                        $sampleValue = '(JSON 필드)';
                    }
                } elseif (in_array($field, $nestedFields)) {
                    // 중첩 필드 (goals.long_term 등)
                    $foundTable = $actualTableName;
                    $sampleValue = '(중첩 필드)';
                } elseif ($field === 'goals') {
                    // goals 배열 필드
                    $foundTable = $actualTableName;
                    $sampleValue = '(배열 필드)';
                } elseif ($field === 'profile_data' && $actualTableName === 'alt42_student_profiles') {
                    // profile_data는 alt42_student_profiles의 JSON 컬럼
                    $foundTable = $actualTableName;
                    $sampleValue = '(JSON 컬럼)';
                } elseif ($field === 'mbti_type') {
                    // mbti_type은 abessi_mbtilog의 mbti 필드에서 가져옴
                    $foundTable = 'abessi_mbtilog';
                    $sampleValue = '(mbti 필드 매핑)';
                } elseif (isset($onboardingFields[$field]) && $actualTableName === 'alt42o_onboarding') {
                    // alt42o_onboarding 전용 필드 - DB 컬럼 존재 여부와 관계없이 매핑 설정
                    $foundTable = 'alt42o_onboarding';
                    // 실제 컬럼이 존재하면 샘플값 조회 시도
                    try {
                        $columns = $DB->get_columns('alt42o_onboarding');
                        if (isset($columns[$field])) {
                            $sampleData = $DB->get_record('alt42o_onboarding', ['userid' => $studentid], $field, IGNORE_MISSING);
                            if ($sampleData && isset($sampleData->$field) && $sampleData->$field !== null && $sampleData->$field !== '') {
                                $sampleValue = is_string($sampleData->$field) ? substr($sampleData->$field, 0, 50) : $sampleData->$field;
                            } else {
                                $sampleValue = '(온보딩 필드)';
                            }
                        } else {
                            $sampleValue = '(온보딩 필드 - 스키마 정의됨)';
                        }
                    } catch (Exception $e) {
                        $sampleValue = '(온보딩 필드)';
                    }
                } elseif (in_array($field, $profileJsonFields) && $actualTableName === 'alt42_student_profiles') {
                    // alt42_student_profiles JSON 필드
                    $foundTable = 'alt42_student_profiles';
                    $sampleValue = '(프로필 JSON 필드)';
                } elseif ($actualTableName === 'alt42_exam_schedule' || $actualTableName === 'alt42t_exam_settings') {
                    // 시험 관련 테이블 필드 - 테이블 매핑 설정
                    $foundTable = $actualTableName;
                    $sampleValue = '(시험 스케줄 필드)';
                } elseif (strpos($actualTableName, 'alt42g_') === 0) {
                    // Agent03 목표 분석 관련 테이블 (alt42g_*)
                    $foundTable = $actualTableName;
                    $sampleValue = '(목표 분석 필드)';
                } elseif ($actualTableName === 'alt42_student_activity') {
                    // Agent04 학생 활동 테이블
                    $foundTable = $actualTableName;
                    $sampleValue = '(학생 활동 필드)';
                } elseif ($actualTableName === 'abessi_today' || $actualTableName === 'abessi_tracking' || $actualTableName === 'abessi_messages') {
                    // abessi 관련 테이블 필드
                    $foundTable = $actualTableName;
                    $sampleValue = '(학습 데이터 필드)';
                } elseif ($actualTableName === 'alt42o_onboarding') {
                    // alt42o_onboarding 필드 - DB 컬럼 존재 여부와 관계없이 매핑 설정
                    $foundTable = 'alt42o_onboarding';
                    $sampleValue = '(온보딩 필드)';
                } elseif ($actualTableName === '_runtime') {
                    // 런타임 필드 - 코드에서 동적 생성
                    $foundTable = '_runtime';
                    $sampleValue = '(런타임 생성)';
                } elseif ($actualTableName === 'user') {
                    // user 테이블 필드
                    $foundTable = 'user';
                    $sampleValue = '(사용자 필드)';
                } else {
                    // 필드가 테이블에 실제로 존재하는지 확인
                    $columns = $DB->get_columns($actualTableName);
                    if (isset($columns[$field])) {
                        $foundTable = $actualTableName;

                        // 데이터가 있으면 샘플 값 가져오기
                        try {
                            $sampleData = $DB->get_record($actualTableName, ['userid' => $studentid], $field, IGNORE_MISSING);
                            if ($sampleData && isset($sampleData->$field) && $sampleData->$field !== null && $sampleData->$field !== '') {
                                $sampleValue = is_string($sampleData->$field) ? substr($sampleData->$field, 0, 50) : $sampleData->$field;
                            }
                        } catch (Exception $e) {
                            // 샘플 데이터 조회 실패는 무시 (테이블명 매핑은 유지)
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error checking mapped table {$mappedTable} for field {$field}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }

    // 2. 명시적 매핑에서 찾지 못한 경우 기존 로직으로 검색
    if (empty($foundTable)) {
        // Agent01 특화 테이블 목록 (우선순위 순서)
        $tablesToCheck = [];

        // agent20_intervention_preparation - 기본 테이블 목록
        $tablesToCheck = [
            ['name' => 'alt42_goinghome', 'type' => 'json'],
            ['name' => 'alt42o_onboarding', 'type' => 'column'],
            ['name' => 'alt42o_learning_assessment_results', 'type' => 'column'],
            ['name' => 'abessi_mbtilog', 'type' => 'column'],
            ['name' => 'alt42_student_profiles', 'type' => 'json'],
        ];


    // 각 테이블 확인
    foreach ($tablesToCheck as $tableInfo) {
        $tableName = $tableInfo['name'];
        $tableType = $tableInfo['type'];
        
        // 테이블 존재 확인 (Moodle의 table_exists()는 mdl_ 접두사 없이 테이블명 필요)
        $tableExists = false;
        try {
            // mdl_ 접두사 제거 후 확인
            $tableNameWithoutPrefix = preg_replace('/^mdl_/', '', $tableName);
            $tableExists = $DB->get_manager()->table_exists(new xmldb_table($tableNameWithoutPrefix));
            if ($tableExists) {
                // 실제 DB 접근 시에는 접두사 없이 사용
                $tableName = $tableNameWithoutPrefix;
            }
            if (!$tableExists) {
                // 접두사 없는 원본 테이블명으로 시도
                $tableExists = $DB->get_manager()->table_exists(new xmldb_table($tableName));
            }
        } catch (Exception $e) {
            continue;
        }
        
        if (!$tableExists) {
            continue;
        }
        
        // JSON 테이블 처리
        if ($tableType === 'json') {
            if ($tableName === 'alt42_goinghome') {
                try {
                    $sampleData = $DB->get_record_sql(
                        "SELECT * FROM {" . str_replace('mdl_', '', $tableName) . "} WHERE userid = ? ORDER BY timecreated DESC LIMIT 1",
                        [$studentid],
                        IGNORE_MISSING
                    );
                    if ($sampleData && isset($sampleData->text)) {
                        $jsonData = json_decode($sampleData->text, true);
                        if (is_array($jsonData) && isset($jsonData[$field])) {
                            $foundTable = $tableName;
                            $sampleValue = is_string($jsonData[$field]) ? substr($jsonData[$field], 0, 50) : $jsonData[$field];
                            break; // 첫 번째로 찾은 테이블 사용
                        }
                    }
                } catch (Exception $e) {
                    continue;
                }
            } elseif ($tableName === 'alt42_student_profiles') {
                try {
                    $profile = $DB->get_record('alt42_student_profiles', ['user_id' => $studentid], '*', IGNORE_MISSING);
                    if ($profile && !empty($profile->profile_data)) {
                        $jsonData = json_decode($profile->profile_data, true);
                        if (is_array($jsonData) && isset($jsonData[$field])) {
                            $foundTable = $tableName;
                            $sampleValue = is_string($jsonData[$field]) ? substr($jsonData[$field], 0, 50) : $jsonData[$field];
                            break; // 첫 번째로 찾은 테이블 사용
                        }
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        } 
        // 일반 컬럼 테이블 처리
        else {
            try {
                // $tableName은 이미 위에서 mdl_ 접두사가 제거된 상태
                // Moodle의 get_columns()은 접두사 없이 테이블명 필요
                $actualTableName = $tableName;

                $columns = $DB->get_columns($actualTableName);
                if (isset($columns[$field])) {
                    $foundTable = $actualTableName;
                    // 데이터가 있으면 샘플 값 가져오기
                    $sampleData = $DB->get_record($actualTableName, ['userid' => $studentid], $field, IGNORE_MISSING);
                    if ($sampleData && isset($sampleData->$field) && $sampleData->$field !== null && $sampleData->$field !== '') {
                        $sampleValue = is_string($sampleData->$field) ? substr($sampleData->$field, 0, 50) : $sampleData->$field;
                    }
                    break; // 첫 번째로 찾은 테이블 사용
                }
            } catch (Exception $e) {
                error_log("Error checking table {$tableName} for field {$field}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                continue;
            }
        }
    } // foreach ($tablesToCheck as $tableInfo) 종료
    } // if (empty($foundTable)) 종료

    // 필드가 테이블에 있으면 데이터가 없어도 테이블명 매핑
    if (!empty($foundTable)) {
        $viewReportsContentForField = isset($viewReportsContent) ? $viewReportsContent : '';
        $dataTypeInfo = identifyDataType($field, $rulesYamlContent ?? '', $dataAccessContent ?? '', $foundTable, $viewReportsContentForField);
        $dbDataExists[] = [
            'field' => $field,
            'table' => $foundTable,
            'type' => $dataTypeInfo['type'] ?? 'unknown',
            'db_applied' => $dataTypeInfo['db_applied'] ?? false,
            'sample' => $sampleValue
        ];
    } else {
        // 디버깅: 필드를 찾지 못한 경우 로그 출력 (주요 필드만)
        if ($agentid === 'agent01_onboarding' && in_array($field, ['academy_experience', 'notes', 'weekly_hours', 'favorite_food', 'favorite_fruit', 'favorite_snack', 'hobbies_interests', 'math_learning_style', 'math_level', 'math_recent_score', 'math_unit_mastery', 'textbooks', 'study_hours_per_week', 'study_style'])) {
            error_log("Field {$field} not found in any table for agent01_onboarding [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
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
        // 설문 데이터 테이블 (survdata)
        if (strpos($tableName, 'onboarding') !== false || 
            strpos($tableName, 'survey') !== false || 
            strpos($tableName, 'goinghome') !== false ||
            strpos($tableName, 'learning_assessment') !== false ||
            strpos($tableName, 'assessment_results') !== false) {
            $type = 'survdata';
            $evidence[] = '테이블명 기반 추론: 설문 데이터';
            $dbApplied = true;
        } 
        // 시스템 데이터 테이블 (sysdata)
        elseif (strpos($tableName, 'user') !== false || 
                strpos($tableName, 'calmness') !== false || 
                strpos($tableName, 'tracking') !== false || 
                strpos($tableName, 'messages') !== false ||
                strpos($tableName, 'mbtilog') !== false) {
            $type = 'sysdata';
            $evidence[] = '테이블명 기반 추론: 시스템 데이터';
            $dbApplied = true;
        }
        // 생성 데이터 테이블 (gendata) - 리포트, 분석 결과 등
        elseif (strpos($tableName, 'reports') !== false ||
                strpos($tableName, 'analysis') !== false) {
            $type = 'gendata';
            $evidence[] = '테이블명 기반 추론: 생성 데이터 (LLM/AI)';
            $dbApplied = true;
        }
    }
    
    // 5. 필드명 패턴 기반 추론
    if ($type === 'unknown') {
        // uidata 패턴: 사용자가 직접 입력하는 필드
        $uiPatterns = ['goal', 'plan', 'question', 'response', 'answer', 'feedback', 'note', 'memo'];
        // survdata 패턴: 설문 응답 필드 (Agent01 온보딩 설문 포함)
        $survPatterns = [
            'calmness', 'pomodoro', 'satisfaction', 'stress', 'anxiety', 'boredom', 'weekly_goal', 'daily_plan',
            // Agent01 온보딩 설문 필드 (qa01~qa16)
            'qa01', 'qa02', 'qa03', 'qa04', 'qa05', 'qa06', 'qa07', 'qa08', 'qa09', 'qa10', 'qa11', 'qa12', 'qa13', 'qa14', 'qa15', 'qa16',
            // 영역별 점수
            'cognitive_score', 'emotional_score', 'behavioral_score', 'overall_total',
            // 온보딩 설문 관련 필드
            'math_learning_style', 'academy_name', 'academy_grade', 'academy_schedule', 'math_recent_score', 'math_weak_units', 'textbooks', 'math_unit_mastery',
            'concept_progress', 'advanced_progress', 'exam_style', 'parent_style', 'feedback_preference', 'learning_notes'
        ];
        // sysdata 패턴: 시스템 자동 생성 필드
        $sysPatterns = ['timecreated', 'timemodified', 'userid', 'id', 'level', 'duration', 'timestart', 'timeend'];
        // gendata 패턴: AI/계산 생성 필드 (리포트, 분석 결과 등)
        $genPatterns = [
            'grade', 'usage', 'count', 'analysis', 'score', 'recommendation', 'diagnosis',
            'report', 'generated', 'gpt', 'summary', 'assessment', 'evaluation'
        ];
        
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

// $allFields는 이미 위에서 생성되었음 (269줄 이후)

// 각 필드에 대한 매핑 분석
foreach ($allFields as $field) {
    $inViewReports = in_array($field, $viewReportsFields);
    $inRulesYaml = in_array($field, $rulesFields);
    $inDataAccess = in_array($field, $dataAccessFields);
    $inDB = false;
    $dbTableName = '';
    
    // $dbDataExists에서 테이블명 확인 (실제 DB에서 확인한 결과 우선)
    foreach ($dbDataExists as $dbData) {
        if ($dbData['field'] === $field && !empty($dbData['table'])) {
            $inDB = true;
            $dbTableName = $dbData['table'];
            break;
        }
    }
    
    // $dbDataExists에서 찾지 못한 경우 $dbFields에서 확인
    if (empty($dbTableName)) {
        foreach ($dbFields as $dbField) {
            $dbFieldName = explode('.', $dbField)[1] ?? $dbField;
            if ($dbFieldName === $field) {
                $inDB = true;
                $dbTableName = explode('.', $dbField)[0] ?? '';
                break;
            }
        }
    }
    
    // 여전히 테이블명을 찾지 못한 경우, Agent01 특화 테이블 구조 직접 확인
    if (empty($dbTableName) && $agentid === 'agent01_onboarding') {
        $agent01Tables = [
            'mdl_alt42g_learning_progress',
            'mdl_alt42g_learning_style',
            'mdl_alt42g_learning_method',
            'mdl_alt42g_learning_goals',
            'mdl_alt42g_additional_info',
            'alt42o_onboarding',
            'alt42o_learning_assessment_results',
            'alt42_student_profiles',
            'abessi_mbtilog'
        ];
        
        foreach ($agent01Tables as $checkTableName) {
            try {
                // 테이블 존재 확인 (Moodle의 table_exists()는 mdl_ 접두사 없이 테이블명 필요)
                $tableNameWithoutPrefix = preg_replace('/^mdl_/', '', $checkTableName);
                $tableExists = $DB->get_manager()->table_exists(new xmldb_table($tableNameWithoutPrefix));
                $actualTableName = $tableNameWithoutPrefix;

                if (!$tableExists) {
                    // 접두사 없는 원본 테이블명으로 재시도
                    $tableExists = $DB->get_manager()->table_exists(new xmldb_table($checkTableName));
                    if ($tableExists) {
                        $actualTableName = $checkTableName;
                    }
                }

                if ($tableExists) {
                    $columns = $DB->get_columns($actualTableName);
                    if (isset($columns[$field])) {
                        $inDB = true;
                        $dbTableName = $actualTableName;
                        break;
                    }
                }
            } catch (Exception $e) {
                error_log("Error checking table {$checkTableName} for field {$field}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                continue;
            }
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
            max-width: 95%;
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
        
        .agent-selector-container select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
            width: 90%;
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
        
        /* Tooltip 스타일 - 폭을 5배로 확대 */
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
            position: relative;
            cursor: help;
        }
        
        .evidence-list li {
            margin-left: 1rem;
        }
        
        /* 증거 열 툴팁 스타일 */
        .evidence-list[title]:hover::after,
        td > span[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-bottom: 8px;
            padding: 12px 16px;
            background: #1f2937;
            color: white;
            font-size: 0.8rem;
            font-weight: normal;
            white-space: pre-wrap;
            max-width: 500px;
            min-width: 300px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            pointer-events: none;
            line-height: 1.5;
            word-wrap: break-word;
        }
        
        .evidence-list[title]:hover::before,
        td > span[title]:hover::before {
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
        
        /* 증거 셀에 position relative 추가 */
        .data-table td {
            position: relative;
        }
    </style>
</head>
<body>
    <!-- 좌측 상단 페이지 메뉴 드롭다운 -->
    <div class="nav-dropdown" style="position: fixed; top: 0; left: 0; z-index: 1000; display: flex; gap: 2px; align-items: flex-start;">
        <select id="pageSelector" onchange="navigateToPage()" style="padding: 10px 15px; border: 2px solid rgba(255,255,255,0.3); border-top: none; border-left: none; border-right: none; background: rgba(255,255,255,0.95); color: #667eea; font-size: 14px; font-weight: bold; cursor: pointer; min-width: 200px; height: 42px; line-height: 1.5; box-sizing: border-box; box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: all 0.3s;">
            <option value="agentmission.html">1. 에이전트 미션</option>
            <option value="questions.html">2. 주요 요청들</option>
            <option value="dataindex.php" selected>3. 데이터 통합</option>
            <option value="rules_viewer.html">4. 에이전트 룰들</option>
            <option value="../../index.php">5. Mathking AI 조교</option>
            <option value="heartbeat_dashboard.html">6. Heartbeat Dashboard</option>
            <option value="../agent22_module_improvement/ui/index.php">7. 에이전트 가드닝</option>
        </select>
    </div>
    
    <div class="container" style="padding-top: 60px;">
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
            <h1>📊 데이터 매핑 분석 리포트 (<a href="dataindex_user.php?userid=<?php echo $studentid; ?>&agentid=<?php echo htmlspecialchars($agentid); ?>" style="color: white; text-decoration: underline; opacity: 0.9; transition: opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.9'">user data</a>)</h1>
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
                        <th>테이블</th>
                        <th>Rules.yaml</th>
                        <th>증거</th>
                        <th>연결</th>
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
                        $dbTable = $mapping['db_table'] ?? '';
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
                            <?php if (!empty($dbTable)): ?>
                                <?php if ($statusValue === '동작'): ?>
                                    <!-- 동작 상태이고 테이블명이 있는 경우: DB에 존재함을 강조 표시 -->
                                    <code style="font-size: 0.85rem; color: #065f46; background: #d1fae5; padding: 2px 6px; border-radius: 4px; font-weight: 600; border: 1px solid #6ee7b7;">
                                        ✅ <?php echo htmlspecialchars($dbTable); ?>
                                    </code>
                                <?php else: ?>
                                    <!-- 일반 표시 -->
                                    <code style="font-size: 0.85rem; color: #6366f1; background: #eef2ff; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($dbTable); ?></code>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if ($statusValue === '동작'): ?>
                                    <!-- 동작 상태인데 테이블명이 없는 경우: 경고 표시 -->
                                    <span style="color: #dc2626; font-size: 0.85rem; font-weight: 600;">⚠️ 테이블명 없음</span>
                                <?php else: ?>
                                    <span style="color: #9ca3af; font-size: 0.85rem;">-</span>
                                <?php endif; ?>
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
                            <?php 
                            // 개선 내용 생성 (툴팁용)
                            $improvementText = '';
                            if ($agentid === 'agent01_onboarding') {
                                $improvementHtml = interpretFieldStatus($mapping, $agentid, $DB, $rulesYamlContent ?? '', $dataAccessContent ?? '', $viewReportsContent ?? '', $studentid);
                                // HTML 태그 제거하고 텍스트만 추출
                                $improvementText = strip_tags($improvementHtml);
                                // HTML 엔티티 디코드
                                $improvementText = html_entity_decode($improvementText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                // 줄바꿈을 공백으로 변환 (툴팁에서 가독성 향상)
                                $improvementText = str_replace(["\n", "\r"], ' ', $improvementText);
                                // 연속된 공백 제거
                                $improvementText = preg_replace('/\s+/', ' ', $improvementText);
                                $improvementText = trim($improvementText);
                            }
                            
                            // 증거 표시
                            if (!empty($mapping['evidence'])): 
                                $tooltipTitle = !empty($improvementText) ? htmlspecialchars($improvementText, ENT_QUOTES, 'UTF-8') : '';
                            ?>
                            <ul class="evidence-list" <?php echo !empty($tooltipTitle) ? 'title="' . $tooltipTitle . '"' : ''; ?>>
                                <?php foreach ($mapping['evidence'] as $ev): ?>
                                <li><?php echo htmlspecialchars($ev); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else: ?>
                            <span style="color: #9ca3af;" <?php echo !empty($improvementText) ? 'title="' . htmlspecialchars($improvementText, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            // 테이블명이 있는 경우 인터페이스 링크 표시
                            $dbTable = $mapping['db_table'] ?? '';
                            $hasLink = false;
                            $interfaceUrl = '';
                            
                            if (!empty($dbTable)) {
                                $interfaceUrl = getInterfaceLinkForTable($dbTable, $agentid);
                                $hasLink = !empty($interfaceUrl);
                            }
                            
                            // 아이콘은 항상 표시, 링크가 있으면 파란색, 없으면 회색
                            if ($hasLink) {
                                echo '<a href="' . htmlspecialchars($interfaceUrl) . '" target="_blank" ';
                                echo 'style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; ';
                                echo 'color: #3b82f6; text-decoration: none; border: 1px solid #3b82f6; border-radius: 4px; ';
                                echo 'font-size: 0.75rem; transition: all 0.2s; background: #eff6ff;" ';
                                echo 'onmouseover="this.style.background=\'#dbeafe\'; this.style.color=\'#1e40af\';" ';
                                echo 'onmouseout="this.style.background=\'#eff6ff\'; this.style.color=\'#3b82f6\';" ';
                                echo 'title="인터페이스 열기: ' . htmlspecialchars($dbTable) . '">';
                                echo '🔗 <span>연결</span>';
                                echo '</a>';
                            } else {
                                // 링크가 없는 경우 회색으로 표시
                                echo '<span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; ';
                                echo 'color: #9ca3af; border: 1px solid #d1d5db; border-radius: 4px; ';
                                echo 'font-size: 0.75rem; background: #f3f4f6;" ';
                                echo 'title="인터페이스 링크 없음">';
                                echo '🔗 <span>연결</span>';
                                echo '</span>';
                            }
                            ?>
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
        // 페이지 네비게이션 함수
        function navigateToPage() {
            const selector = document.getElementById('pageSelector');
            const selectedPage = selector.value;
            if (selectedPage) {
                // 현재 선택된 에이전트 정보 가져오기
                const agentSelector = document.getElementById('agentSelector');
                const currentAgent = agentSelector ? agentSelector.value : null;
                
                // URL 구성
                let url = selectedPage;
                if (currentAgent && selectedPage === 'dataindex.php') {
                    // dataindex.php의 경우 쿼리 파라미터로 에이전트 정보 전달
                    url += '?agentid=' + currentAgent;
                    const urlParams = new URLSearchParams(window.location.search);
                    const studentid = urlParams.get('studentid');
                    if (studentid) {
                        url += '&studentid=' + studentid;
                    }
                } else if (currentAgent) {
                    // 해시로 에이전트 정보 전달
                    url += '#' + currentAgent;
                }
                
                window.location.href = url;
            }
        }
        
        // 현재 페이지에 맞게 dropdown 선택
        const currentPage = window.location.pathname.split('/').pop();
        const pageSelector = document.getElementById('pageSelector');
        if (pageSelector) {
            if (currentPage === 'agentmission.html') {
                pageSelector.value = 'agentmission.html';
            } else if (currentPage === 'questions.html') {
                pageSelector.value = 'questions.html';
            } else if (currentPage === 'dataindex.php') {
                pageSelector.value = 'dataindex.php';
            } else if (currentPage === 'rules_viewer.html') {
                pageSelector.value = 'rules_viewer.html';
            } else if (currentPage === 'heartbeat_dashboard.html') {
                pageSelector.value = 'heartbeat_dashboard.html';
            } else if (window.location.pathname.includes('agent22_module_improvement')) {
                pageSelector.value = '../agent22_module_improvement/ui/index.php';
            } else if (currentPage === 'index.php' || currentPage === '') {
                pageSelector.value = '../../index.php';
            }
        }
        
        // 에이전트 변경 함수 - 각 에이전트 폴더의 dataindex.php로 이동
        function changeAgent() {
            const agentSelector = document.getElementById('agentSelector');
            const selectedAgent = agentSelector.value;
            // 현재 경로에서 agents/ 폴더 위치를 찾아서 해당 에이전트 폴더로 이동
            const currentPath = window.location.pathname;
            const agentsIndex = currentPath.indexOf('/agents/');
            if (agentsIndex !== -1) {
                const basePath = currentPath.substring(0, agentsIndex + 8); // '/agents/' 포함
                window.location.href = basePath + selectedAgent + '/dataindex.php';
            } else {
                /// fallback: 상대 경로 사용
                window.location.href = '../' + selectedAgent + '/dataindex.php';
            }
        }
        
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


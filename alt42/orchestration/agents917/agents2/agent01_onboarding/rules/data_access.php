<?php
 

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;
// require_login()은 호출하는 쪽에서 이미 처리됨 (agent_garden.controller.php)
// require_login();

// xmldb_table 클래스 로드를 위해 ddllib.php require
if (isset($CFG) && isset($CFG->libdir)) {
    require_once($CFG->libdir.'/ddllib.php');
}

/**
 * 시스템 DB 연결 상태 진단 및 상세 리포트 생성
 * 
 * @param int $studentid 학생 ID
 * @return string 상세한 진단 리포트 문자열
 */ 
function getSystemHealthReport($studentid) {
    global $DB;
    $report = [];
    $errors = [];
    $warnings = [];
    $info = [];  
    
    // 1. 필수 테이블 존재 여부 확인 
    $requiredTables = [
        'alt42o_onboarding' => '온보딩 메인 테이블',
        'user' => '사용자 기본 정보 테이블',
        'alt42_student_profiles' => '학생 프로필 테이블 (선택)'
    ];
    
    $tableStatus = [];
    foreach ($requiredTables as $table => $description) {
        $exists = $DB->get_manager()->table_exists(new xmldb_table($table));
        if (!$exists) {
            // 접두사 포함해서 재시도
            $exists = $DB->get_manager()->table_exists(new xmldb_table('mdl_'.$table));
        }
        
        $tableStatus[$table] = [
            'exists' => $exists,
            'description' => $description
        ];
        
        if (!$exists && $table !== 'alt42_student_profiles') {
            $errors[] = "❌ 테이블 누락: {$table} ({$description})";
        } elseif (!$exists) {
            $warnings[] = "⚠️ 테이블 없음 (선택): {$table} ({$description})";
        } else {
            $info[] = "✅ 테이블 존재: {$table}";
        }
    }
    
    // 2. alt42o_onboarding 테이블 상세 진단
    $onboardingDetails = [];
    if ($tableStatus['alt42o_onboarding']['exists']) {
        try {
            // 테이블 구조 확인 (필수 컬럼)
            $requiredColumns = [
                'math_learning_style' => '수학 학습 스타일',
                'academy_name' => '학원명',
                'math_recent_score' => '최근 수학 점수',
                'textbooks' => '교재 정보',
                'math_unit_mastery' => '단원 마스터리'
            ];
            
            $columns = $DB->get_columns('alt42o_onboarding');
            $missingColumns = [];
            
            foreach ($requiredColumns as $col => $desc) {
                if (!isset($columns[$col])) {
                    $missingColumns[] = "{$col} ({$desc})";
                }
            }
            
            if (!empty($missingColumns)) {
                $warnings[] = "⚠️ 필수 컬럼 누락: " . implode(", ", $missingColumns);
            } else {
                $info[] = "✅ 필수 컬럼 모두 존재";
            }
            
            // 데이터 존재 여부 확인
            $onboarding = $DB->get_record('alt42o_onboarding', ['userid' => $studentid], '*', IGNORE_MISSING);
            if ($onboarding) {
                $dataFields = [];
                if (!empty($onboarding->math_learning_style)) $dataFields[] = 'math_learning_style';
                if (!empty($onboarding->academy_name)) $dataFields[] = 'academy_name';
                if (!empty($onboarding->math_recent_score)) $dataFields[] = 'math_recent_score';
                if (!empty($onboarding->textbooks)) $dataFields[] = 'textbooks';
                
                if (count($dataFields) > 0) {
                    $info[] = "✅ 온보딩 데이터 존재 (" . count($dataFields) . "개 필드 채워짐)";
                } else {
                    $warnings[] = "⚠️ 온보딩 데이터는 있으나 필수 필드가 비어있음 (userid: {$studentid})";
                }
            } else {
                $errors[] = "❌ 온보딩 데이터 없음 (userid: {$studentid})";
            }
            
        } catch (Exception $e) {
            $errors[] = "❌ 테이블 조회 오류: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]";
        }
    }
    
    // 3. DB 연결 상태 확인
    try {
        $testQuery = $DB->get_record_sql("SELECT 1 as test", [], IGNORE_MISSING);
        if ($testQuery) {
            $info[] = "✅ DB 연결 정상";
        }
    } catch (Exception $e) {
        $errors[] = "❌ DB 연결 오류: " . $e->getMessage();
    }
    
    // 4. 리포트 생성
    $reportLines = [];
    $reportLines[] = "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";
    $reportLines[] = "📊 시스템 DB 연결 상태 진단 결과";
    $reportLines[] = "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";
    
    if (empty($errors) && empty($warnings)) {
        $reportLines[] = "✅ 상태: 정상";
        $reportLines[] = "";
        $reportLines[] = "모든 필수 테이블이 존재하고 데이터가 정상적으로 조회됩니다.";
    } else {
        if (!empty($errors)) {
            $reportLines[] = "❌ 상태: 오류 발견";
        } else {
            $reportLines[] = "⚠️ 상태: 주의 필요";
        }
        $reportLines[] = "";
    }
    
    // 오류 메시지
    if (!empty($errors)) {
        $reportLines[] = "【발견된 문제】";
        foreach ($errors as $error) {
            $reportLines[] = $error;
        }
        $reportLines[] = "";
    }
    
    // 경고 메시지
    if (!empty($warnings)) {
        $reportLines[] = "【주의사항】";
        foreach ($warnings as $warning) {
            $reportLines[] = $warning;
        }
        $reportLines[] = "";
    }
    
    // 정보 메시지
    if (!empty($info)) {
        $reportLines[] = "【확인된 사항】";
        foreach ($info as $inf) {
            $reportLines[] = $inf;
        }
        $reportLines[] = "";
    }
    
    // 개선 방안
    if (!empty($errors) || !empty($warnings)) {
        $reportLines[] = "【개선 방안】";
        
        if (in_array('alt42o_onboarding', array_keys(array_filter($tableStatus, function($s) { return !$s['exists']; })))) {
            $reportLines[] = "1. DB 스키마 동기화 필요:";
            $reportLines[] = "   → alt42/orchestration/agents/agent01_onboarding/rules/1_run_db_sync.php 실행";
            $reportLines[] = "   → 또는 브라우저에서 해당 파일 URL 접속하여 테이블 생성";
            $reportLines[] = "";
        }
        
        if (in_array('math_learning_style', $missingColumns ?? [])) {
            $reportLines[] = "2. 필수 컬럼 추가 필요:";
            $reportLines[] = "   → math_learning_style, academy_name, math_recent_score, textbooks, math_unit_mastery";
            $reportLines[] = "   → 1_db_schema_sync.sql 파일의 ALTER TABLE 문 실행";
            $reportLines[] = "";
        }
        
        $onboarding = $DB->get_record('alt42o_onboarding', ['userid' => $studentid], 'id', IGNORE_MISSING);
        if (!$onboarding) {
            $reportLines[] = "3. 온보딩 데이터 입력 필요:";
            $reportLines[] = "   → 온보딩 설문 페이지에서 학생 정보 입력";
            $reportLines[] = "   → 또는 view_reports.php를 통해 데이터 입력";
            $reportLines[] = "";
        }
        
        $reportLines[] = "4. 추가 확인 사항:";
        $reportLines[] = "   → dataindex.php에서 필드 매핑 상태 확인";
        $reportLines[] = "   → https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent_orchestration/dataindex.php?agentid=agent01_onboarding";
        $reportLines[] = "";
    }
    
    $reportLines[] = "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";
    
    return implode("\n", $reportLines);
}

/**
 * 학생 정보 및 온보딩 컨텍스트 수집
 * 
 * @param int $studentid 학생 ID
 * @return array 학생 정보 컨텍스트 데이터
 */
function getOnboardingContext($studentid) {
    global $DB;
    
    // student_id 검증
    if (empty($studentid)) {
        error_log("Warning: getOnboardingContext called with empty studentid [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        return ['student_id' => null];
    }
    
    // 기본 컨텍스트 구조 정의
    $context = [
        'student_id' => $studentid,
        'math_level' => null,
        'math_confidence' => null,
        'exam_style' => null,
        'parent_style' => null,
        'study_hours_per_week' => null,
        'goals' => [
            'long_term' => null,
            'mid_term' => null,
            'short_term' => null
        ],
        'advanced_progress' => null,
        'concept_progress' => null,
        'study_style' => null,
        'mbti_type' => null,
        // 수학학원 시스템 특화 필드 (rules.yaml 필수)
        'math_learning_style' => null,  // 계산형/개념형/응용형
        'academy_name' => null,
        'academy_grade' => null,
        'academy_schedule' => null,
        'math_recent_score' => null,
        'math_recent_ranking' => null,
        'math_weak_units' => [],
        'textbooks' => [],
        'math_unit_mastery' => [],
        // 추가 필드
        'feedback_preference' => null,
        'learning_notes' => null,
        // 선호/취미 정보 (mdl_alt42g_additional_info)
        'favorite_food' => null,
        'favorite_fruit' => null,
        'favorite_snack' => null,
        'hobbies_interests' => null,
        'fandom_yn' => null,
        'data_consent' => null,
        // 학습 진도 정보 (mdl_alt42g_learning_progress)
        'notes' => null,
        'weekly_hours' => null,
        'academy_experience' => null,
        // 학습 목표 정보 (mdl_alt42g_learning_goals)
        'short_term_goal' => null,
        'mid_term_goal' => null,
        'long_term_goal' => null,
        'goal_note' => null
    ];
    
    // student_id 백업 (예외 발생 시에도 보존)
    $studentIdBackup = $studentid;
    
    try {
        // 1. 학생 기본 정보 (mdl_user)
        $student = $DB->get_record('user', ['id' => $studentid], '*', MUST_EXIST);
        $context['student_name'] = $student->firstname . ' ' . $student->lastname;
        $context['email'] = $student->email;
        $context['last_access'] = $student->lastaccess;
        
        // new_student_flag 계산 (30일 이내 가입 = 신규 학생)
        $daysSinceRegistration = floor((time() - $student->timecreated) / 86400);
        $context['new_student_flag'] = $daysSinceRegistration <= 30;
        
        // 2. MBTI 정보 (mdl_abessi_mbtilog)
        if ($DB->get_manager()->table_exists(new xmldb_table('abessi_mbtilog'))) {
            $mbtiLog = $DB->get_record_sql(
                "SELECT * FROM {abessi_mbtilog} WHERE userid = ? ORDER BY timecreated DESC LIMIT 1",
                [$studentid]
            );
            if ($mbtiLog && !empty($mbtiLog->mbti)) {
                $context['mbti_type'] = strtoupper($mbtiLog->mbti);
            }
        }
        
        // 3. 온보딩 정보 (mdl_alt42o_onboarding) - 메인 소스
        $onboarding = null;
        // 테이블 존재 여부 확인 (접두사 없이 시도)
        if ($DB->get_manager()->table_exists(new xmldb_table('alt42o_onboarding'))) {
            $onboarding = $DB->get_record('alt42o_onboarding', ['userid' => $studentid], '*', IGNORE_MISSING);
        } elseif ($DB->get_manager()->table_exists(new xmldb_table('mdl_alt42o_onboarding'))) {
            $onboarding = $DB->get_record('mdl_alt42o_onboarding', ['userid' => $studentid], '*', IGNORE_MISSING);
        }
        
        if ($onboarding) {
            // 3.1 기본 학습 정보 매핑
            $context['math_confidence'] = $onboarding->math_confidence ?? null;
            $context['exam_style'] = $onboarding->exam_style ?? null;
            $context['parent_style'] = $onboarding->parent_style ?? null;
            $context['study_style'] = $onboarding->problem_preference ?? null; // problem_preference를 study_style로 매핑
            
            // 수학 수준 조합 (과정 + 학년)
            if (!empty($onboarding->course_level)) {
                $context['math_level'] = $onboarding->course_level . (!empty($onboarding->grade_detail) ? ' ' . $onboarding->grade_detail : '');
            }
            
            // 3.2 진도 정보 매핑
            $context['concept_progress'] = $onboarding->concept_progress ?? null;
            $context['advanced_progress'] = $onboarding->advanced_progress ?? null;
            $context['concept_level'] = $onboarding->concept_level ?? null;
            $context['advanced_level'] = $onboarding->advanced_level ?? null;
            
            // 3.3 목표 정보 매핑
            $context['goals']['long_term'] = $onboarding->long_term_goal ?? null;
            $context['goals']['mid_term'] = $onboarding->mid_term_goal ?? null;
            $context['goals']['short_term'] = $onboarding->short_term_goal ?? null;
            $context['study_hours_per_week'] = $onboarding->vacation_hours ?? null;
            
            // 3.4 수학학원 시스템 특화 필드 매핑 (DB 스키마 동기화 후 컬럼 사용)
            $context['math_learning_style'] = $onboarding->math_learning_style ?? null;
            $context['academy_name'] = $onboarding->academy_name ?? null;
            $context['academy_grade'] = $onboarding->academy_grade ?? null;
            $context['academy_schedule'] = $onboarding->academy_schedule ?? null;
            $context['math_recent_score'] = $onboarding->math_recent_score ?? null;
            $context['textbooks'] = !empty($onboarding->textbooks) ? explode(',', $onboarding->textbooks) : null;
            
            // 단원 마스터리 (JSON 디코딩)
            if (!empty($onboarding->math_unit_mastery)) {
                $decodedMastery = json_decode($onboarding->math_unit_mastery, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $context['math_unit_mastery'] = $decodedMastery;
                }
            }
            
            // 기타
            $context['feedback_preference'] = $onboarding->feedback_preference ?? null;
            $context['learning_notes'] = $onboarding->learning_notes ?? null;
            
            // profile_update_flag
            if (isset($onboarding->updated_at) && isset($onboarding->created_at)) {
                $context['profile_update_flag'] = strtotime($onboarding->updated_at) > strtotime($onboarding->created_at);
            } else {
                $context['profile_update_flag'] = false;
            }
        }
        
        // 4. 기존 프로필 (mdl_alt42_student_profiles) - 백업 데이터 소스
        // 온보딩 테이블에 데이터가 없는 경우 여기서 보완
        if ($DB->get_manager()->table_exists(new xmldb_table('alt42_student_profiles'))) {
            $profile = $DB->get_record('alt42_student_profiles', ['user_id' => $studentid], '*', IGNORE_MISSING);
            if ($profile) {
                if (empty($context['math_level']) && !empty($profile->math_level)) {
                    $context['math_level'] = $profile->math_level;
                }
                // JSON 데이터 파싱
                if (!empty($profile->profile_data)) {
                    $profileData = json_decode($profile->profile_data, true);
                    if (is_array($profileData)) {
                        if (empty($context['math_confidence'])) $context['math_confidence'] = $profileData['math_confidence'] ?? null;
                        if (empty($context['study_style'])) $context['study_style'] = $profileData['study_style'] ?? null;
                    }
                }
            }
        }
        
        // 5. 추가 정보 (mdl_alt42g_additional_info) - MATHKING DB에서 가져오기
        // favorite_food, favorite_fruit, favorite_snack, hobbies_interests 등
        try {
            // omniui/config.php에서 MATHKING DB 설정 로드
            $omniCandidates = [
                $_SERVER['DOCUMENT_ROOT'] . '/moodle/local/augmented_teacher/alt42/omniui/config.php',
                dirname(__DIR__, 3) . '/omniui/config.php',
                dirname(__DIR__, 4) . '/omniui/config.php'
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
                
                // additional_info 테이블
                $stmt = $pdo->prepare("SELECT * FROM mdl_alt42g_additional_info WHERE userid = ?");
                $stmt->execute([$studentid]);
                if ($row = $stmt->fetch()) {
                    if (!empty($row['favorite_food'])) $context['favorite_food'] = $row['favorite_food'];
                    if (!empty($row['favorite_fruit'])) $context['favorite_fruit'] = $row['favorite_fruit'];
                    if (!empty($row['favorite_snack'])) $context['favorite_snack'] = $row['favorite_snack'];
                    if (!empty($row['hobbies_interests'])) $context['hobbies_interests'] = $row['hobbies_interests'];
                    if (isset($row['fandom_yn'])) $context['fandom_yn'] = (bool)$row['fandom_yn'];
                    if (isset($row['data_consent'])) $context['data_consent'] = (bool)$row['data_consent'];
                }
                
                // learning_progress 테이블
                $stmt = $pdo->prepare("SELECT * FROM mdl_alt42g_learning_progress WHERE userid = ?");
                $stmt->execute([$studentid]);
                if ($row = $stmt->fetch()) {
                    if (!empty($row['notes'])) $context['notes'] = $row['notes'];
                    if (isset($row['weekly_hours'])) $context['weekly_hours'] = (int)$row['weekly_hours'];
                    if (!empty($row['academy_experience'])) $context['academy_experience'] = $row['academy_experience'];
                }
                
                // learning_goals 테이블
                $stmt = $pdo->prepare("SELECT * FROM mdl_alt42g_learning_goals WHERE userid = ?");
                $stmt->execute([$studentid]);
                if ($row = $stmt->fetch()) {
                    if (!empty($row['short_term_goal'])) $context['short_term_goal'] = $row['short_term_goal'];
                    if (!empty($row['mid_term_goal'])) $context['mid_term_goal'] = $row['mid_term_goal'];
                    if (!empty($row['long_term_goal'])) $context['long_term_goal'] = $row['long_term_goal'];
                    if (!empty($row['goal_note'])) $context['goal_note'] = $row['goal_note'];
                }
            }
        } catch (Exception $e) {
            error_log("Error loading MATHKING DB data: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
    } catch (Exception $e) {
        error_log("Error in getOnboardingContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        if (!isset($context['student_id'])) $context['student_id'] = $studentIdBackup;
    }
    
    // student_id 보장
    if (!isset($context['student_id']) || empty($context['student_id'])) {
        $context['student_id'] = $studentIdBackup;
    }
    
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    
    // 시스템 헬스 체크 리포트 추가
    $context['system_health_report'] = getSystemHealthReport($studentid);
    
    return $context;
}

/**
 * 룰 엔진용 컨텍스트 준비 (getOnboardingContext 래퍼)
 * 빈 값에 대해 기본값을 제공하여 온톨로지 파이프라인이 정상 작동하도록 함
 */
function prepareRuleContext($studentid) {
    $context = getOnboardingContext($studentid);
    
    // 온톨로지 파이프라인에 필요한 필수 필드에 기본값 제공
    // 이 기본값들은 실제 DB 데이터가 없을 때만 사용됨
    $defaults = [
        // 학습 스타일 관련
        'math_learning_style' => '개념형',  // 계산형/개념형/응용형
        'study_style' => '자기주도형',       // 자기주도형/지도필요형/혼합형
        'exam_style' => '꾸준형',            // 꾸준형/벼락치기형/혼합형
        
        // 수학 수준 및 자신감
        'math_level' => '중위권',            // 상위권/중상위권/중위권/중하위권/하위권
        'math_confidence' => 5,              // 1-10 스케일
        'math_stress_level' => 5,            // 1-10 스케일
        
        // 진도 정보
        'concept_progress' => '현재 학년 과정',
        'advanced_progress' => '미진행',
        'current_progress_position' => '학기 중반',
        'math_unit_mastery' => [],
        
        // 학교/학원 정보
        'student_grade' => '중2',
        'school_name' => '(학교 미입력)',
        'academy_name' => '(학원 미입력)',
        'academy_grade' => '(반 미입력)',
        
        // 목표
        'goals' => [
            'long_term' => '수학 실력 향상',
            'mid_term' => '다음 시험 성적 향상',
            'short_term' => '현재 단원 이해'
        ]
    ];
    
    // 빈 값에만 기본값 적용
    foreach ($defaults as $key => $defaultValue) {
        if (!isset($context[$key]) || $context[$key] === null || $context[$key] === '' || 
            (is_array($context[$key]) && empty($context[$key]))) {
            $context[$key] = $defaultValue;
        }
    }
    
    // goals 배열 내부도 처리
    if (isset($defaults['goals']) && is_array($defaults['goals'])) {
        foreach ($defaults['goals'] as $goalKey => $goalDefault) {
            if (!isset($context['goals'][$goalKey]) || empty($context['goals'][$goalKey])) {
                $context['goals'][$goalKey] = $goalDefault;
            }
        }
    }
    
    // 온톨로지 변수 매핑을 위한 별칭 추가 (SchemaLoader 매핑과 완전 일치)
    // snake_case, camelCase, has프리픽스 모두 지원 (신뢰도 향상)
    $aliasMapping = [
        // === 기본 프로퍼티 ===
        'concept_progress' => 'conceptProgressLevel',
        'advanced_progress' => 'advancedProgressLevel',
        'math_learning_style' => 'mathLearningStyle',
        'study_style' => 'studyStyle',
        'exam_style' => 'examPreparationStyle',
        'math_confidence' => 'mathSelfConfidence',
        'math_level' => 'mathLevel',
        'math_stress_level' => 'mathStressLevel',
        'student_grade' => 'gradeLevel',
        'school_name' => 'schoolName',
        'academy_name' => 'academyName',
        'academy_grade' => 'academyGrade',
        
        // === has 프리픽스 관계 프로퍼티 (온톨로지 직접 매핑) ===
        'concept_progress' => 'hasConceptProgress',
        'advanced_progress' => 'hasAdvancedProgress',
        'math_unit_mastery' => 'hasUnitMastery',
        'current_progress_position' => 'hasCurrentPosition',
        'math_learning_style' => 'hasMathLearningStyle',
        'study_style' => 'hasStudyStyle',
        'exam_style' => 'hasExamStyle',
        'math_confidence' => 'hasMathConfidence',
        'math_level' => 'hasMathLevel',
        'math_stress_level' => 'hasMathStressLevel',
        'student_grade' => 'hasStudentGrade',
        'school_name' => 'hasSchool',
        'academy_name' => 'hasAcademy',
        'academy_grade' => 'hasAcademyGrade',
        'textbooks' => 'hasTextbooks'
    ];
    
    foreach ($aliasMapping as $snakeCase => $camelCase) {
        if (isset($context[$snakeCase]) && !isset($context[$camelCase])) {
            $context[$camelCase] = $context[$snakeCase];
        }
    }
    
    // === 역방향 매핑도 추가 (온톨로지 프로퍼티 → 컨텍스트 키) ===
    $reverseMapping = [
        'gradeLevel' => 'student_grade',
        'schoolName' => 'school_name',
        'academyName' => 'academy_name',
        'academyGrade' => 'academy_grade',
        'conceptProgressLevel' => 'concept_progress',
        'advancedProgressLevel' => 'advanced_progress',
        'mathLearningStyle' => 'math_learning_style',
        'studyStyle' => 'study_style',
        'examPreparationStyle' => 'exam_style',
        'mathSelfConfidence' => 'math_confidence',
        'mathLevel' => 'math_level',
        'mathStressLevel' => 'math_stress_level'
    ];
    
    foreach ($reverseMapping as $camelCase => $snakeCase) {
        if (isset($context[$snakeCase]) && !isset($context[$camelCase])) {
            $context[$camelCase] = $context[$snakeCase];
        }
    }
    
    // === UnitMastery 배열을 올바른 형식으로 변환 ===
    if (isset($context['math_unit_mastery']) && is_array($context['math_unit_mastery'])) {
        // 배열이 비어있지 않으면 JSON 문자열이 아닌 구조화된 형식 유지
        $masteryData = $context['math_unit_mastery'];
        if (!empty($masteryData)) {
            // UnitMastery 인스턴스 형태로 변환
            $formattedMastery = [];
            foreach ($masteryData as $unit => $level) {
                if (is_string($unit) && !empty($unit)) {
                    $formattedMastery[] = [
                        'unitName' => $unit,
                        'masteryLevel' => $level
                    ];
                } elseif (is_array($level) && isset($level['unitName'])) {
                    $formattedMastery[] = $level;
                }
            }
            $context['math_unit_mastery_formatted'] = $formattedMastery;
            $context['hasUnitMastery'] = $formattedMastery;
        }
    }
    
    return $context;
}
?>

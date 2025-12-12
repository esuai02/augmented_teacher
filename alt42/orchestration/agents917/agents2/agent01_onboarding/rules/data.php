<?php
/* 
   
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

/**
 * 학생 온보딩 데이터 수집
 * 
 * @param int $studentid 학생 ID
 * @return array 학생 온보딩 컨텍스트 데이터
 */
function getOnboardingContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'math_level' => null,
        'math_confidence' => null,
        'exam_style' => null,
        'parent_style' => null,
        'study_hours_per_week' => null,
        'goals' => [
            'long_term' => null
        ],
        'advanced_progress' => null,
        'concept_progress' => null,
        'study_style' => null,
        'mbti_type' => null,
        // 수학학원 시스템 특화 필드
        'math_learning_style' => null,  // 계산형, 개념형, 응용형
        'academy_name' => null,
        'academy_grade' => null,
        'academy_schedule' => null,
        'math_recent_score' => null,
        'math_recent_ranking' => null,
        'math_weak_units' => [],
        'textbooks' => [],
        'math_unit_mastery' => []
    ];
    
    try {
        // 학생 기본 정보
        $student = $DB->get_record('user', ['id' => $studentid], '*', MUST_EXIST);
        $context['student_name'] = $student->firstname . ' ' . $student->lastname;
        $context['email'] = $student->email;
        $context['last_access'] = $student->lastaccess;
        
        // MBTI 정보 (mdl_abessi_mbtilog 테이블)
        if ($DB->get_manager()->table_exists(new xmldb_table('mdl_abessi_mbtilog'))) {
            $mbtiLog = $DB->get_record_sql(
                "SELECT * FROM {abessi_mbtilog} WHERE userid = ? ORDER BY timecreated DESC LIMIT 1",
                [$studentid]
            );
            if ($mbtiLog && !empty($mbtiLog->mbti)) {
                $context['mbti_type'] = strtoupper($mbtiLog->mbti);
            }
        }
        
        // 프로필 정보 (mdl_alt42_student_profiles 테이블)
        if ($DB->get_manager()->table_exists(new xmldb_table('mdl_alt42_student_profiles'))) {
            $profile = $DB->get_record('mdl_alt42_student_profiles', ['user_id' => $studentid], '*', IGNORE_MISSING);
            if ($profile) {
                // 프로필 데이터 매핑
                // JSON 필드인 경우 디코딩
                if (isset($profile->profile_data) && !empty($profile->profile_data)) {
                    $profileData = json_decode($profile->profile_data, true);
                    if (is_array($profileData)) {
                        $context['math_level'] = $profileData['math_level'] ?? $profileData['mathLevel'] ?? null;
                        $context['math_confidence'] = $profileData['math_confidence'] ?? $profileData['mathConfidence'] ?? null;
                        $context['study_style'] = $profileData['study_style'] ?? $profileData['studyStyle'] ?? null;
                    }
                }
                // 직접 필드가 있는 경우
                if (isset($profile->math_level)) {
                    $context['math_level'] = $profile->math_level;
                }
                if (isset($profile->math_confidence)) {
                    $context['math_confidence'] = $profile->math_confidence;
                }
                if (isset($profile->study_style)) {
                    $context['study_style'] = $profile->study_style;
                }
            }
        }
        
        // 온보딩 정보 (mdl_alt42_onboarding 테이블) - rules.yaml에서 필요한 모든 필드 조회
        if ($DB->get_manager()->table_exists(new xmldb_table('mdl_alt42_onboarding'))) {
            $onboarding = $DB->get_record('mdl_alt42_onboarding', ['user_id' => $studentid], '*', IGNORE_MISSING);
            if ($onboarding) {
                $context['math_level'] = $onboarding->math_level ?? $context['math_level'];
                $context['math_confidence'] = $onboarding->math_confidence ?? $context['math_confidence'];
                $context['exam_style'] = $onboarding->exam_style ?? null;
                $context['parent_style'] = $onboarding->parent_style ?? null;
                $context['study_hours_per_week'] = $onboarding->study_hours_per_week ?? null;
                $context['concept_progress'] = $onboarding->concept_progress ?? null;
                $context['advanced_progress'] = $onboarding->advanced_progress ?? null;
                $context['study_style'] = $onboarding->study_style ?? $context['study_style'];
                
                // goals 필드 (JSON)
                if (isset($onboarding->goals) && !empty($onboarding->goals)) {
                    $goalsData = json_decode($onboarding->goals, true);
                    if (is_array($goalsData)) {
                        $context['goals']['long_term'] = $goalsData['long_term'] ?? $goalsData['longTerm'] ?? null;
                    }
                }
            }
        } else {
            // 테이블이 없을 경우 로그 기록
            error_log("Warning: mdl_alt42_onboarding table does not exist. [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // TODO: 학원 정보 테이블이 생성되면 아래 주석 해제
        // if ($DB->get_manager()->table_exists(new xmldb_table('mdl_alt42_academy_info'))) {
        //     $academy = $DB->get_record('mdl_alt42_academy_info', ['userid' => $studentid], '*', IGNORE_MISSING);
        //     if ($academy) {
        //         $context['academy_name'] = $academy->academy_name ?? null;
        //         $context['academy_grade'] = $academy->academy_grade ?? null;
        //         $context['academy_schedule'] = json_decode($academy->academy_schedule ?? '[]', true);
        //     }
        // }
        
        // TODO: 수학 학습 스타일 테이블이 생성되면 아래 주석 해제
        // if ($DB->get_manager()->table_exists(new xmldb_table('mdl_alt42_math_learning_style'))) {
        //     $mathStyle = $DB->get_record('mdl_alt42_math_learning_style', ['userid' => $studentid], '*', IGNORE_MISSING);
        //     if ($mathStyle) {
        //         $context['math_learning_style'] = $mathStyle->math_learning_style ?? null;
        //     }
        // }
        
    } catch (Exception $e) {
        error_log("Error in getOnboardingContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

/**
 * 학원 정보 데이터 수집 (수학학원 시스템 특화)
 * 
 * @param int $studentid 학생 ID
 * @return array 학원 정보 컨텍스트 데이터
 */
function getAcademyInfo($studentid) {
    global $DB;
    
    $academyInfo = [
        'academy_name' => null,
        'academy_grade' => null,
        'academy_schedule' => [],
        'academy_current_unit' => null,
        'academy_homework' => []
    ];
    
    try {
        // TODO: 학원 정보 테이블에서 데이터 조회
        // $academy = $DB->get_record('mdl_alt42_academy_info', ['userid' => $studentid], '*', IGNORE_MISSING);
        // if ($academy) {
        //     $academyInfo['academy_name'] = $academy->academy_name ?? null;
        //     $academyInfo['academy_grade'] = $academy->academy_grade ?? null;
        //     $academyInfo['academy_schedule'] = json_decode($academy->academy_schedule ?? '[]', true);
        // }
        
    } catch (Exception $e) {
        error_log("Error in getAcademyInfo: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $academyInfo;
}

/**
 * 수학 학습 스타일 데이터 수집
 * 
 * @param int $studentid 학생 ID
 * @return string 수학 학습 스타일 (계산형, 개념형, 응용형)
 */
function getMathLearningStyle($studentid) {
    global $DB;
    
    $mathStyle = null;
    
    try {
        // TODO: 수학 학습 스타일 테이블에서 데이터 조회
        // if ($DB->get_manager()->table_exists(new xmldb_table('mdl_alt42_math_learning_style'))) {
        //     $style = $DB->get_record('mdl_alt42_math_learning_style', ['userid' => $studentid], '*', IGNORE_MISSING);
        //     if ($style) {
        //         $mathStyle = $style->math_learning_style ?? null;
        //     }
        // }
        
    } catch (Exception $e) {
        error_log("Error in getMathLearningStyle: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $mathStyle;
}

/**
 * 룰 평가를 위한 컨텍스트 준비 (수학학원 시스템 통합)
 * 
 * @param int $studentid 학생 ID
 * @return array 룰 엔진용 컨텍스트
 */
function prepareRuleContext($studentid) {
    $context = getOnboardingContext($studentid);
    $academyInfo = getAcademyInfo($studentid);
    $mathStyle = getMathLearningStyle($studentid);
    
    // 학원 정보 및 수학 학습 스타일 통합
    $context = array_merge($context, $academyInfo);
    $context['math_learning_style'] = $mathStyle;
    
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}

<?php
// Moodle config는 이미 로드되어 있다고 가정 (호출하는 쪽에서 로드)
// 하지만 안전을 위해 확인
if (!isset($DB)) {
    // config.php가 로드되지 않은 경우에만 로드
    if (file_exists("/home/moodle/public_html/moodle/config.php")) {
        include_once("/home/moodle/public_html/moodle/config.php");
    } else {
        // 상대 경로로 시도
        $possiblePaths = [
            __DIR__ . '/../../../../../../config.php',
            __DIR__ . '/../../../../../../../config.php',
        ];
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                include_once($path);
                break;
            }
        }
    }
}

global $DB, $USER, $CFG;

/* 
?�� 1. ?�험 ?�정 �?범위 ?�보 (12)

?�험�?(중간/기말/?�원?��? ??

?�험 ?�작??/ 종료??

D-day (?��? ?�수)

과목�?(?�학/과학/�?�� ??

?�험 범위(?�원�? 쪽수, 개념목록 ?�함)

?�험 범위 ??주요 개념 ?�이??분포

?�험 범위 ???�형 분류(기본문제/?�술??고난??

?�험 범위 ??출제 비중(?�원�?%)

과거 ?�일 ?�원 출제 빈도

?�교�?출제 경향 ?�이??

?�험 중요??(?�신 반영 비율, ?�적 ?�향??

?�험 준�??�작??�?계획 착수??

?�� 2. 교재·콘텐�??�보 (10)

?�교 교재�? 출판??

?�원 교재�?(??RPM/블랙?�벨 ??

교재�??�원 커버리�?(?�험범위 ?��?%)

교재�??�이???�차 지??

교재�??�???�도(�?문항)

?�라??콘텐�?강의/문제?�?? ?�용 ?�역

교재�??�료 ?�이지/?�원 ??

교재�??��? ?�습??%)

AI 추천 콘텐�?매칭 ?�역

교재�?문제?�형 ?�그(개념/?�용/?�화)

?�� 3. ?�원 관???�이??(10)

?�원�?/ �?/ ?�벨

?�원 ?�업 ?�일 �??�간

?�원 진도 ?�원

?�원 진도?� ?�교 진도??차이(�??�위)

?�원 과제 목록 �??�료??%)

?�원 과제�??�이???�그

?�원 모의고사 ?�정

?�원 모의고사 ?�수 �??�차

?�원 교재�?진도??

?�원 ?�생???�드�??�용(?�심 코멘???�약)

?�️ 4. ?�습?�간 �?진행�??�이??(10)

?�루 ?�균 공�??�간

?�험 ?��??�적 공�??�간

?�험범위�??�습?�간 분포

교재�??�습?�간 비율

주간 목표?��??�제 진도(%)

?�습 지???�인(?�간 부�??�로/?�해 미흡 ??

계획 ?�성�?최근 4�??�균)

?�험??집중?�간?�(Heatmap)

?�험 직전 ?�습 몰입??변??

?�습 ?�료 ?��?복습 비율

?�� 5. ?�력 �??�해???�이??(10)

?�원�??�답�?%)

개념�??�해??지??

문제?�형�??�공�?

?�답?�트 ?�록 빈도

?�수??계산/개념 구분)

?�용문항 ?�공�?

최근 3??모의 ?�수 변?�율

?�원�??�요?�간 ?�균

?�전 모의?�스???�간???�?�율

?�력 ?�장�??�회 ?��?% ?�상)

?�� 6. ?�습?�략 ?�립???�요??변??(10)

목표 ?�수

목표 ?�급

목표 ?�성 ?�이??추정�?

목표 ?�수 ?��?부�??�수

?�재 ?�습 ?�도 ?��??�상 ?�성�?

?�험까�? ?��? 가?�시�?

?�습 집중 블록(?�효?�간?�)

?�기/중기/?�기 ?�략 모드 ?�정�?

?�험???�로 ?�적�?

?�습 리스???�자(?�간·?�해·?�서 �??�세 ?�인)

?�� 7. ?�습과정 분석 ?�이??(10)

최근 ?�습 루틴(?�간?�, ?�동?�형)

개념 ??문제 ??복습 ?�름 비율

루틴 ?��? ?�공�?%)

?�습 ?�름 중단 지???�턴)

문제?�???�도 변??추이

�?�� ?�위(?�습 ?�션??개념 ??

반복 ?�습 간격(?�페?�싱 ?�이??

최근 집중 ?��? ?�간(?�균 �??�위)

복습 ?�점�?기억 ?��???

?�습?�율 ?�수(?�취???�간 비율)

?�️ 8. ?�서·?�기 ?�태 (10)

?�험 관??불안지??

?�험 ?�신�??�벨

?�험 준�?�??�트?�스 지??

?�로??(최근 7???�균)

?�습 ?�기?�벨(0~1.0)

?�험 ??긴장??변??

감정 ?�복?�도(?�드�???개선?�간)

?�습 컨디??변???�턴

?�패 ???�복 반응 로그

격려 메시지 ?�호 ???? 차분/?�동??

?�� 9. ?�과 �??��? ?�이??(10)

최근 ?�험 ?�수

목표 ?��??�수 차이

?�급 �?백분??

?�험 ?�간 ???�?�율

객�????�술???�수 비율

?�험 �??�수 ?�턴

?�험 �?문제???�균 ?�?�시�?

?�험 �?마�?�??�???�공�?

?�험 직전 복습 ?�과???�수 반영�?

?�수 ?�상 기여 ?�인(콘텐츠별 비중)

?�� 10. ?�스???�업 �?AI 분석??메�??�이??(8)

?�습 ?�탈 ?�벤??로그

계획 무너�?감�? ?�래�?

AI 개입 ?�점 로그 (Agent 21 ?�업??

?�드�?반응 ?�간

?��?�??�담 ?�벤??로그 (Agent 6 ?�계)

?�생???�드�?반영�?

?�그?�처 루틴 매칭 결과

?�호?�용 ?�과 지??개입 ??변?�율)
*/

// config.php는 이미 파일 시작 부분에서 로드됨
// require_login()은 호출하는 쪽에서 이미 처리됨 (agent_garden.controller.php)

/**
 * ?�험 ?�정 ?�이???�집
 * 
 * @param int $studentid ?�생 ID
 * @return array ?�험 ?�정 컨텍?�트 ?�이??
 */
function getExamScheduleContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'upcoming_exams' => [],
        'exam_urgency' => null,
        'exam_subjects' => [],
        'd_day' => null
    ];
    
    try {
        // ?�험 ?�정 조회 (alt42_exam_schedule ?�이�? - Moodle은 자동으로 mdl_ 접두사 추가)
        $examSchedule = $DB->get_record('alt42_exam_schedule', ['userid' => $studentid], '*', IGNORE_MISSING);
        
        if ($examSchedule) {
            $today = time();
            $examDate = isset($examSchedule->exam_date) ? $examSchedule->exam_date : null;
            
            if ($examDate) {
                $d_day = floor(($examDate - $today) / 86400);
                $context['upcoming_exams'][] = [
                    'exam_name' => $examSchedule->exam_name ?? '',
                    'exam_date' => $examDate,
                    'd_day' => $d_day,
                    'target_score' => $examSchedule->target_score ?? null
                ];
                $context['d_day'] = $d_day;
                $context['exam_urgency'] = $d_day <= 3 ? 'urgent' : ($d_day <= 10 ? 'moderate' : 'normal');
            }
        }
        
    } catch (Exception $e) {
        error_log("Error in getExamScheduleContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

/**
 * ?�원 ?�보 ?�이???�집 (?�학?�원 ?�스???�화)
 * 
 * @param int $studentid ?�생 ID
 * @return array ?�원 ?�보 컨텍?�트 ?�이??
 */
function getAcademyInfo($studentid) {
    global $DB;
    
    $academyInfo = [
        'academy_name' => null,
        'academy_grade' => null,
        'academy_schedule' => [],
        'academy_current_unit' => null,
        'academy_expected_progress' => null,
        'academy_homework' => [],
        'academy_mock_exam_schedule' => [],
        'academy_teacher_feedback' => null
    ];
    
    try {
        // TODO: ?�원 ?�보 ?�이블에???�이??조회
        // $academy = $DB->get_record('mdl_alt42_academy_info', ['userid' => $studentid], '*', IGNORE_MISSING);
        // if ($academy) {
        //     $academyInfo['academy_name'] = $academy->academy_name ?? null;
        //     $academyInfo['academy_grade'] = $academy->academy_grade ?? null;
        //     $academyInfo['academy_schedule'] = json_decode($academy->schedule_json ?? '[]', true);
        // }
        
        // TODO: ?�원 진도 ?�이블에???�이??조회
        // $progress = $DB->get_record('mdl_alt42_academy_progress', ['userid' => $studentid], '*', IGNORE_MISSING);
        // if ($progress) {
        //     $academyInfo['academy_current_unit'] = $progress->current_unit ?? null;
        //     $academyInfo['academy_expected_progress'] = json_decode($progress->expected_progress_json ?? '[]', true);
        // }
        
        // TODO: ?�원 과제 ?�이블에???�이??조회
        // $homeworks = $DB->get_records('mdl_alt42_academy_homework', ['userid' => $studentid]);
        // foreach ($homeworks as $hw) {
        //     $academyInfo['academy_homework'][] = [
        //         'textbook_name' => $hw->textbook_name ?? '',
        //         'unit' => $hw->unit ?? '',
        //         'assignment_amount' => $hw->assignment_amount ?? 0,
        //         'completion_rate' => $hw->completion_rate ?? 0,
        //         'time_spent' => $hw->time_spent ?? 0
        //     ];
        // }
        
        // TODO: ?�원 모의고사 ?�이블에???�이??조회
        // $mockExams = $DB->get_records('mdl_alt42_academy_mock_exam', ['userid' => $studentid]);
        // foreach ($mockExams as $exam) {
        //     $academyInfo['academy_mock_exam_schedule'][] = [
        //         'exam_date' => $exam->exam_date ?? null,
        //         'target_ranking' => $exam->target_ranking ?? null,
        //         'current_ranking' => $exam->current_ranking ?? null
        //     ];
        // }
        
    } catch (Exception $e) {
        error_log("Error in getAcademyInfo: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $academyInfo;
}

/**
 * ?�원-?�교 진도 비교 분석
 * 
 * @param array $academyProgress ?�원 진도 ?�보
 * @param array $schoolProgress ?�교 진도 ?�보
 * @return array 진도 비교 결과
 */
function compareAcademySchoolProgress($academyProgress, $schoolProgress) {
    $comparison = [
        'academy_ahead' => false,
        'school_ahead' => false,
        'sync_needed' => false,
        'recommendation' => ''
    ];
    
    // TODO: ?�원 진도?� ?�교 진도 비교 로직 구현
    // ?�원�?진도 비교?�여 ?�행/?�행 ?��? ?�단
    
    return $comparison;
}

/**
 * ?�원 과제 ?�료??계산
 * 
 * @param array $homeworkList ?�원 과제 목록
 * @return float ?�료??(0.0 ~ 1.0)
 */
function calculateAcademyHomeworkCompletionRate($homeworkList) {
    if (empty($homeworkList)) {
        return 0.0;
    }
    
    $totalAssignments = 0;
    $completedAssignments = 0;
    
    foreach ($homeworkList as $hw) {
        $amount = $hw['assignment_amount'] ?? 0;
        $completionRate = $hw['completion_rate'] ?? 0.0;
        
        $totalAssignments += $amount;
        $completedAssignments += $amount * $completionRate;
    }
    
    return $totalAssignments > 0 ? ($completedAssignments / $totalAssignments) : 0.0;
}

/**
 * �??��?�??�한 컨텍?�트 준�?(?�학?�원 ?�스???�합)
 */
function prepareRuleContext($studentid) {
    $context = getExamScheduleContext($studentid);
    $academyInfo = getAcademyInfo($studentid);
    
    // ?�원 ?�보�?컨텍?�트???�합
    $context = array_merge($context, $academyInfo);
    
    // ?�원 과제 ?�료??계산 (안전성 개선)
    $context['academy_homework_completion_rate'] = calculateAcademyHomeworkCompletionRate($academyInfo['academy_homework'] ?? []);
    
    // ?�원-?�교 진도 비교
    // TODO: ?�교 진도 ?�보 ?�집 ??비교
    // $schoolProgress = getSchoolProgress($studentid);
    // $context['academy_school_progress_comparison'] = compareAcademySchoolProgress($academyInfo, $schoolProgress);
    
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}

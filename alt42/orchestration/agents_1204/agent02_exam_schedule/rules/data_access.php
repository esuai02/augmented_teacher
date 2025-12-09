<?php
/**
 * Agent 02 - Exam Schedule Data Provider
 * File: agent02_exam_schedule/rules/data_access.php
 *
 * 데이터 소스:
 * - mdl_alt42_exam_schedule: 시험 일정 및 범위 정보
 * - mdl_alt42_academy_info: 학원 기본 정보
 * - mdl_alt42_academy_progress: 학원 진도 정보
 * - mdl_alt42_academy_homework: 학원 과제 정보
 * - mdl_alt42_academy_mock_exam: 학원 모의고사 일정
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent02
 * @author      AI Agent Integration Team
 * @version     2.0.0
 * @updated     2025-12-09
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents_1204/agent02_exam_schedule/rules/data_access.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 공통 모듈 로드
require_once(__DIR__ . '/../../engine_core/validation/DataSourceValidator.php');
require_once(__DIR__ . '/../../engine_core/errors/AgentErrorHandler.php');

/**
 * Agent 02 데이터 소스 정의
 */
define('AGENT02_DATA_SOURCES', [
    [
        'table' => 'alt42_exam_schedule',
        'fields' => ['userid', 'exam_name', 'exam_type', 'exam_date', 'target_score', 'exam_scope', 'created_at']
    ],
    [
        'table' => 'alt42_academy_info',
        'fields' => ['userid', 'academy_name', 'academy_grade', 'schedule_json']
    ],
    [
        'table' => 'alt42_academy_progress',
        'fields' => ['userid', 'current_unit', 'expected_progress_json']
    ],
    [
        'table' => 'alt42_academy_homework',
        'fields' => ['userid', 'textbook_name', 'unit', 'assignment_amount', 'completion_rate', 'time_spent']
    ],
    [
        'table' => 'alt42_academy_mock_exam',
        'fields' => ['userid', 'exam_date', 'target_ranking', 'current_ranking', 'score']
    ]
]);

define('AGENT02_ID', 'Agent02');

/**
 * 시험 일정 데이터 수집
 *
 * @param int $studentid 학생 ID
 * @return array 시험 일정 컨텍스트 데이터
 */
function getExamScheduleContext($studentid) {
    global $DB;

    $context = [
        'student_id' => $studentid,
        'upcoming_exams' => [],
        'exam_urgency' => null,
        'exam_subjects' => [],
        'd_day' => null,
        'validation_status' => null,
        'data_quality' => []
    ];

    try {
        // 1. 데이터 소스 검증
        $validationResult = validate_data_sources(AGENT02_DATA_SOURCES, $studentid, AGENT02_ID);
        $context['validation_status'] = $validationResult;

        // 검증 실패 시에도 가능한 데이터는 수집 시도
        if (!$validationResult['success']) {
            $errorHandler = new AgentErrorHandler(AGENT02_ID);
            $errorHandler->log(
                'Data source validation failed - partial data collection',
                ErrorSeverity::WARNING,
                ['missing' => $validationResult['missing']]
            );
        }

        // 2. 경고 처리 (NULL 값 필드)
        if (!empty($validationResult['warnings'])) {
            $context['data_quality']['null_warnings'] = $validationResult['warnings'];
        }

        // 3. 시험 일정 조회 (테이블 존재 확인 후)
        $validator = new DataSourceValidator();
        if ($validator->tableExists('alt42_exam_schedule')) {
            $examSchedules = $DB->get_records_sql(
                "SELECT * FROM {alt42_exam_schedule}
                 WHERE userid = ? AND exam_date >= ?
                 ORDER BY exam_date ASC",
                [$studentid, time()]
            );

            if ($examSchedules) {
                $today = time();
                $closestDDay = null;

                foreach ($examSchedules as $exam) {
                    $examDate = isset($exam->exam_date) ? $exam->exam_date : null;
                    $dDay = $examDate ? floor(($examDate - $today) / 86400) : null;

                    if ($closestDDay === null || ($dDay !== null && $dDay < $closestDDay)) {
                        $closestDDay = $dDay;
                    }

                    $examData = [
                        'id' => $exam->id,
                        'exam_name' => $exam->exam_name ?? '',
                        'exam_type' => $exam->exam_type ?? 'regular',
                        'exam_date' => $examDate,
                        'd_day' => $dDay,
                        'target_score' => $exam->target_score ?? null,
                        'exam_scope' => $exam->exam_scope ?? null
                    ];

                    // 시험 범위 JSON 파싱
                    if (!empty($exam->exam_scope)) {
                        $scopeData = json_decode($exam->exam_scope, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $examData['exam_scope_parsed'] = $scopeData;
                        }
                    }

                    $context['upcoming_exams'][] = $examData;

                    // 과목 수집
                    if (!empty($exam->subject) && !in_array($exam->subject, $context['exam_subjects'])) {
                        $context['exam_subjects'][] = $exam->subject;
                    }
                }

                $context['d_day'] = $closestDDay;
                $context['exam_urgency'] = calculateExamUrgency($closestDDay);
            }
        }

    } catch (Exception $e) {
        $errorResponse = AgentErrorHandler::handle($e, AGENT02_ID, 'getExamScheduleContext');
        $context['error'] = $errorResponse;
    }

    return $context;
}

/**
 * 시험 긴급도 계산
 *
 * @param int|null $dDay D-Day 값
 * @return string 긴급도 레벨
 */
function calculateExamUrgency($dDay) {
    if ($dDay === null) {
        return 'none';
    }
    if ($dDay <= 3) {
        return 'urgent';
    }
    if ($dDay <= 7) {
        return 'high';
    }
    if ($dDay <= 14) {
        return 'moderate';
    }
    return 'normal';
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
        'academy_expected_progress' => null,
        'academy_homework' => [],
        'academy_mock_exam_schedule' => [],
        'academy_teacher_feedback' => null
    ];

    $validator = new DataSourceValidator();

    try {
        // 1. 학원 기본 정보 조회
        if ($validator->tableExists('alt42_academy_info')) {
            $academy = $DB->get_record('alt42_academy_info', ['userid' => $studentid], '*', IGNORE_MISSING);
            if ($academy) {
                $academyInfo['academy_name'] = $academy->academy_name ?? null;
                $academyInfo['academy_grade'] = $academy->academy_grade ?? null;

                // 스케줄 JSON 파싱
                if (!empty($academy->schedule_json)) {
                    $scheduleData = json_decode($academy->schedule_json, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $academyInfo['academy_schedule'] = $scheduleData;
                    }
                }
            }
        }

        // 2. 학원 진도 정보 조회
        if ($validator->tableExists('alt42_academy_progress')) {
            $progress = $DB->get_record('alt42_academy_progress', ['userid' => $studentid], '*', IGNORE_MISSING);
            if ($progress) {
                $academyInfo['academy_current_unit'] = $progress->current_unit ?? null;

                // 예상 진도 JSON 파싱
                if (!empty($progress->expected_progress_json)) {
                    $progressData = json_decode($progress->expected_progress_json, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $academyInfo['academy_expected_progress'] = $progressData;
                    }
                }
            }
        }

        // 3. 학원 과제 목록 조회
        if ($validator->tableExists('alt42_academy_homework')) {
            $homeworks = $DB->get_records('alt42_academy_homework', ['userid' => $studentid]);
            foreach ($homeworks as $hw) {
                $academyInfo['academy_homework'][] = [
                    'id' => $hw->id,
                    'textbook_name' => $hw->textbook_name ?? '',
                    'unit' => $hw->unit ?? '',
                    'assignment_amount' => $hw->assignment_amount ?? 0,
                    'completion_rate' => $hw->completion_rate ?? 0.0,
                    'time_spent' => $hw->time_spent ?? 0,
                    'due_date' => $hw->due_date ?? null
                ];
            }
        }

        // 4. 학원 모의고사 일정 조회
        if ($validator->tableExists('alt42_academy_mock_exam')) {
            $mockExams = $DB->get_records_sql(
                "SELECT * FROM {alt42_academy_mock_exam}
                 WHERE userid = ?
                 ORDER BY exam_date ASC",
                [$studentid]
            );
            foreach ($mockExams as $exam) {
                $academyInfo['academy_mock_exam_schedule'][] = [
                    'id' => $exam->id,
                    'exam_date' => $exam->exam_date ?? null,
                    'target_ranking' => $exam->target_ranking ?? null,
                    'current_ranking' => $exam->current_ranking ?? null,
                    'score' => $exam->score ?? null
                ];
            }
        }

    } catch (Exception $e) {
        $errorHandler = new AgentErrorHandler(AGENT02_ID);
        $errorHandler->log(
            'Error collecting academy info: ' . $e->getMessage(),
            ErrorSeverity::ERROR,
            ['file' => __FILE__, 'line' => __LINE__]
        );
    }

    return $academyInfo;
}

/**
 * 학원-학교 진도 비교 분석
 *
 * @param array $academyProgress 학원 진도 정보
 * @param array $schoolProgress 학교 진도 정보
 * @return array 진도 비교 결과
 */
function compareAcademySchoolProgress($academyProgress, $schoolProgress) {
    $comparison = [
        'academy_ahead' => false,
        'school_ahead' => false,
        'sync_needed' => false,
        'gap_units' => 0,
        'recommendation' => ''
    ];

    // 학원 진도와 학교 진도가 모두 있을 때만 비교
    if (empty($academyProgress) || empty($schoolProgress)) {
        $comparison['recommendation'] = '진도 정보가 부족하여 비교할 수 없습니다.';
        return $comparison;
    }

    // 진도 비교 로직 (단원 기반)
    $academyUnit = is_array($academyProgress) ? ($academyProgress['current_unit'] ?? 0) : 0;
    $schoolUnit = is_array($schoolProgress) ? ($schoolProgress['current_unit'] ?? 0) : 0;

    if ($academyUnit > $schoolUnit) {
        $comparison['academy_ahead'] = true;
        $comparison['gap_units'] = $academyUnit - $schoolUnit;
        $comparison['recommendation'] = '학원이 학교보다 ' . $comparison['gap_units'] . '단원 앞서 있습니다. 예습 효과를 극대화하세요.';
    } elseif ($schoolUnit > $academyUnit) {
        $comparison['school_ahead'] = true;
        $comparison['gap_units'] = $schoolUnit - $academyUnit;
        $comparison['sync_needed'] = true;
        $comparison['recommendation'] = '학교가 학원보다 ' . $comparison['gap_units'] . '단원 앞서 있습니다. 학원 진도 조정이 필요합니다.';
    } else {
        $comparison['recommendation'] = '학원과 학교 진도가 동일합니다.';
    }

    return $comparison;
}

/**
 * 학원 과제 완료율 계산
 *
 * @param array $homeworkList 학원 과제 목록
 * @return array 완료율 분석 결과
 */
function calculateAcademyHomeworkCompletionRate($homeworkList) {
    $analysis = [
        'overall_rate' => 0.0,
        'total_assignments' => 0,
        'completed_assignments' => 0,
        'pending_assignments' => [],
        'time_invested' => 0
    ];

    if (empty($homeworkList)) {
        return $analysis;
    }

    $totalAssignments = 0;
    $completedAssignments = 0;
    $totalTimeSpent = 0;

    foreach ($homeworkList as $hw) {
        $amount = isset($hw['assignment_amount']) ? (int)$hw['assignment_amount'] : 0;
        $completionRate = isset($hw['completion_rate']) ? (float)$hw['completion_rate'] : 0.0;
        $timeSpent = isset($hw['time_spent']) ? (int)$hw['time_spent'] : 0;

        $totalAssignments += $amount;
        $completedAssignments += $amount * $completionRate;
        $totalTimeSpent += $timeSpent;

        // 미완료 과제 추적
        if ($completionRate < 1.0 && $amount > 0) {
            $analysis['pending_assignments'][] = [
                'textbook' => $hw['textbook_name'] ?? '',
                'unit' => $hw['unit'] ?? '',
                'remaining' => $amount * (1 - $completionRate)
            ];
        }
    }

    $analysis['total_assignments'] = $totalAssignments;
    $analysis['completed_assignments'] = round($completedAssignments);
    $analysis['overall_rate'] = $totalAssignments > 0 ? ($completedAssignments / $totalAssignments) : 0.0;
    $analysis['time_invested'] = $totalTimeSpent;

    return $analysis;
}

/**
 * 룰 엔진을 위한 컨텍스트 준비 (수학학원 시스템 통합)
 *
 * @param int $studentid 학생 ID
 * @return array 룰 컨텍스트 데이터
 */
function prepareRuleContext($studentid) {
    $context = getExamScheduleContext($studentid);
    $academyInfo = getAcademyInfo($studentid);

    // 학원 정보를 컨텍스트에 통합
    $context = array_merge($context, $academyInfo);

    // 학원 과제 완료율 분석
    $homeworkAnalysis = calculateAcademyHomeworkCompletionRate($academyInfo['academy_homework']);
    $context['academy_homework_analysis'] = $homeworkAnalysis;
    $context['academy_homework_completion_rate'] = $homeworkAnalysis['overall_rate'];

    // 타임스탬프 및 에이전트 정보
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    $context['agent_id'] = AGENT02_ID;

    return $context;
}

/**
 * 데이터 소스 사전 검증 (API 엔드포인트용)
 *
 * @param int $studentid 학생 ID
 * @return array 검증 결과
 */
function validateAgent02DataSources($studentid) {
    return validate_data_sources(AGENT02_DATA_SOURCES, $studentid, AGENT02_ID);
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * DB 관련 정보
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 참조 테이블:
 *
 * 1. mdl_alt42_exam_schedule
 *    - id (int): PK
 *    - userid (int): 학생 ID
 *    - exam_name (varchar): 시험명 (중간고사, 기말고사 등)
 *    - exam_type (varchar): 시험 유형 (regular, mock, academy)
 *    - exam_date (int): 시험 날짜 (timestamp)
 *    - target_score (int): 목표 점수
 *    - exam_scope (text): JSON 형식 시험 범위
 *    - created_at (int): 생성 시간
 *
 * 2. mdl_alt42_academy_info
 *    - id (int): PK
 *    - userid (int): 학생 ID
 *    - academy_name (varchar): 학원명
 *    - academy_grade (varchar): 학원 내 등급
 *    - schedule_json (text): JSON 형식 수업 일정
 *
 * 3. mdl_alt42_academy_progress
 *    - id (int): PK
 *    - userid (int): 학생 ID
 *    - current_unit (varchar): 현재 진도 단원
 *    - expected_progress_json (text): JSON 형식 예상 진도
 *
 * 4. mdl_alt42_academy_homework
 *    - id (int): PK
 *    - userid (int): 학생 ID
 *    - textbook_name (varchar): 교재명
 *    - unit (varchar): 단원
 *    - assignment_amount (int): 과제량
 *    - completion_rate (float): 완료율 (0.0~1.0)
 *    - time_spent (int): 소요 시간 (분)
 *    - due_date (int): 마감일 (timestamp)
 *
 * 5. mdl_alt42_academy_mock_exam
 *    - id (int): PK
 *    - userid (int): 학생 ID
 *    - exam_date (int): 모의고사 날짜 (timestamp)
 *    - target_ranking (int): 목표 등수
 *    - current_ranking (int): 현재 등수
 *    - score (int): 점수
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */

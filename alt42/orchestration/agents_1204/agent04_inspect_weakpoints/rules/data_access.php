<?php
/**
 * Agent 04 - Problem Activity Data Provider
 * File: agent04_problem_activity/rules/data_access.php
 *
 * 데이터 소스:
 * - mdl_alt42_student_activity: 학생 활동 선택 및 활동 유형 데이터
 *   (userid, main_category, sub_activity, behavior_type, survey_responses, created_at)
 *   - main_category: concept_understanding, type_learning, problem_solving, error_notes, qa, review, pomodoro
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent04
 * @author      AI Agent Integration Team
 * @version     1.1.0
 * @updated     2025-12-09
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents_1204/agent04_inspect_weakpoints/rules/data_access.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 공통 모듈 로드
require_once(__DIR__ . '/../../engine_core/validation/DataSourceValidator.php');
require_once(__DIR__ . '/../../engine_core/errors/AgentErrorHandler.php');

/**
 * Agent 04 데이터 소스 정의
 */
define('AGENT04_DATA_SOURCES', [
    [
        'table' => 'alt42_student_activity',
        'fields' => ['userid', 'main_category', 'sub_activity', 'behavior_type', 'survey_responses', 'created_at']
    ]
]);

define('AGENT04_ID', 'Agent04');

/**
 * 문제 활동 데이터 수집
 *
 * @param int $studentid 학생 ID
 * @return array 문제 활동 컨텍스트 데이터
 */
function getProblemActivityContext($studentid) {
    global $DB;

    $context = [
        'student_id' => $studentid,
        'recent_activities' => [],
        'activity_patterns' => [],
        'main_categories' => [],
        'validation_status' => null,
        'data_quality' => []
    ];

    try {
        // 1. 데이터 소스 검증 (새로운 표준 검증 모듈 사용)
        $validationResult = validate_data_sources(AGENT04_DATA_SOURCES, $studentid, AGENT04_ID);
        $context['validation_status'] = $validationResult;

        if (!$validationResult['success']) {
            // 검증 실패 시 에러 로깅 및 빈 컨텍스트 반환
            $errorHandler = new AgentErrorHandler(AGENT04_ID);
            $errorHandler->log(
                'Data source validation failed',
                ErrorSeverity::WARNING,
                ['missing' => $validationResult['missing']]
            );
            return $context;
        }

        // 2. 경고 처리 (NULL 값 필드)
        if (!empty($validationResult['warnings'])) {
            $context['data_quality']['null_warnings'] = $validationResult['warnings'];
        }

        // 3. 학생 활동 데이터 조회
        $activities = $DB->get_records_sql(
            "SELECT * FROM {alt42_student_activity}
             WHERE userid = ?
             ORDER BY created_at DESC
             LIMIT 20",
            [$studentid]
        );

        if ($activities) {
            $categoryCount = [];
            foreach ($activities as $activity) {
                $activityData = [
                    'id' => $activity->id,
                    'main_category' => $activity->main_category ?? null,
                    'sub_activity' => $activity->sub_activity ?? null,
                    'behavior_type' => $activity->behavior_type ?? null,
                    'created_at' => $activity->created_at ?? null
                ];

                if (isset($activity->survey_responses)) {
                    $activityData['survey_responses'] = json_decode($activity->survey_responses, true);
                }

                $context['recent_activities'][] = $activityData;

                // 카테고리별 집계
                if (isset($activity->main_category)) {
                    $categoryCount[$activity->main_category] = ($categoryCount[$activity->main_category] ?? 0) + 1;
                }
            }

            $context['activity_patterns'] = $categoryCount;
            $context['main_categories'] = array_keys($categoryCount);
        }

    } catch (Exception $e) {
        // 표준 에러 핸들러 사용
        $errorResponse = AgentErrorHandler::handle($e, AGENT04_ID, 'getProblemActivityContext');
        $context['error'] = $errorResponse;
    }

    return $context;
}

/**
 * 룰 엔진을 위한 컨텍스트 준비
 *
 * @param int $studentid 학생 ID
 * @return array 룰 컨텍스트 데이터
 */
function prepareRuleContext($studentid) {
    $context = getProblemActivityContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    $context['agent_id'] = AGENT04_ID;
    return $context;
}

/**
 * 데이터 소스 사전 검증 (API 엔드포인트용)
 *
 * @param int $studentid 학생 ID
 * @return array 검증 결과
 */
function validateAgent04DataSources($studentid) {
    return validate_data_sources(AGENT04_DATA_SOURCES, $studentid, AGENT04_ID);
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * DB 관련 정보
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 참조 테이블: mdl_alt42_student_activity
 *
 * 필드:
 * - id (int): PK
 * - userid (int): 학생 ID (FK → mdl_user.id)
 * - main_category (varchar): 주요 카테고리
 *   가능 값: concept_understanding, type_learning, problem_solving, error_notes, qa, review, pomodoro
 * - sub_activity (varchar): 세부 활동
 * - behavior_type (varchar): 행동 유형
 * - survey_responses (text): JSON 형식 설문 응답
 * - created_at (int): 생성 시간 (timestamp)
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */

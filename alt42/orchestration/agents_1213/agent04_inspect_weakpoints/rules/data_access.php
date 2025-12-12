<?php
/**
 * Agent 04 - Problem Activity Data Provider
 * File: agent04_inspect_weakpoints/rules/data_access.php
 *
 * 데이터 소스:
 * - mdl_alt42_student_activity: 학생 활동 선택 및 활동 유형 데이터
 *   (userid, main_category, sub_activity, behavior_type, survey_responses, created_at)
 *   - main_category: concept_understanding, type_learning, problem_solving, error_notes, qa, review, pomodoro
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_inspect_weakpoints/rules/data_access.php
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent04
 * @version     1.1.0
 * @updated     2025-12-09
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 표준 인프라 로드
require_once(__DIR__ . '/../../engine_core/validation/DataValidator.php');
require_once(__DIR__ . '/../../engine_core/errors/AgentErrorHandler.php');

// Agent 04 데이터 소스 정의
define('AGENT04_DATA_SOURCES', [
    [
        'table' => 'mdl_alt42_student_activity',
        'fields' => ['userid', 'main_category', 'sub_activity', 'behavior_type', 'survey_responses', 'created_at'],
        'required' => true
    ],
    [
        'table' => 'mdl_user',
        'fields' => ['id', 'firstname', 'lastname'],
        'required' => true
    ]
]);

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
        'validation' => null
    ];

    try {
        // DataValidator를 사용한 데이터 소스 검증
        $validator = new DataValidator($DB, $studentid, 'agent04');
        $validation = $validator->validateDataSources(AGENT04_DATA_SOURCES);
        $context['validation'] = $validation;

        if (!$validation['valid']) {
            // 검증 실패 시 로깅
            AgentErrorHandler::log(
                'agent04',
                'Data validation failed: ' . json_encode($validation['missing']),
                'warning',
                'getProblemActivityContext',
                __FILE__,
                __LINE__
            );

            // 필수 테이블이 없으면 빈 컨텍스트 반환
            return $context;
        }

        // 학생 활동 데이터 조회 (mdl_alt42_student_activity 테이블)
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
        $response = AgentErrorHandler::handle($e, 'agent04', 'getProblemActivityContext');
        $context['error'] = $response['error'];
    }

    return $context;
}

/**
 * 규칙 평가를 위한 컨텍스트 준비
 *
 * @param int $studentid 학생 ID
 * @return array 규칙 평가용 컨텍스트
 */
function prepareRuleContext($studentid) {
    global $DB;

    try {
        $context = getProblemActivityContext($studentid);
        $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
        $context['agent_id'] = 'agent04';
        return $context;

    } catch (Exception $e) {
        $response = AgentErrorHandler::handle($e, 'agent04', 'prepareRuleContext');
        return [
            'student_id' => $studentid,
            'timestamp' => date('Y-m-d\TH:i:s\Z'),
            'agent_id' => 'agent04',
            'error' => $response['error']
        ];
    }
}

/**
 * Agent 04 데이터 소스 검증 (독립 실행용)
 *
 * @param int $studentid 학생 ID
 * @return array 검증 결과
 */
function validateAgent04DataSources($studentid) {
    global $DB;

    try {
        $validator = new DataValidator($DB, $studentid, 'agent04');
        return $validator->validateDataSources(AGENT04_DATA_SOURCES);

    } catch (Exception $e) {
        return AgentErrorHandler::handle($e, 'agent04', 'validateAgent04DataSources');
    }
}

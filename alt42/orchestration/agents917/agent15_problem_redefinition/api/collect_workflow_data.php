<?php
/**
 * Workflow Data Collection API
 * File: orchestration/api/collect_workflow_data.php:1
 *
 * Step 2~14의 워크플로우 데이터를 수집하여 반환합니다.
 * Agent 15 (문제 재정의)에서 사용됩니다.
 */

header('Content-Type: application/json');

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

try {
    // POST 데이터 받기
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (!$input) {
        throw new Exception('Invalid JSON input (file: collect_workflow_data.php, line: 19)');
    }

    $userId = $input['userId'] ?? $USER->id;

    if (!$userId) {
        throw new Exception('User ID is required (file: collect_workflow_data.php, line: 25)');
    }

    // 데이터 수집 결과 배열
    $collectedData = [
        'userId' => $userId,
        'timestamp' => date('Y-m-d H:i:s'),
        'steps' => []
    ];

    /**
     * Step 2: 시험 일정
     */
    $step2Data = [
        'step' => 2,
        'title' => '시험 일정',
        'data' => null
    ];

    // mdl_alt42_exam_schedule 테이블에서 데이터 가져오기
    $examSchedule = $DB->get_record('alt42_exam_schedule', ['userid' => $userId], '*', IGNORE_MISSING);
    if ($examSchedule) {
        $step2Data['data'] = [
            'exam_date' => $examSchedule->exam_date ?? null,
            'd_day' => $examSchedule->d_day ?? null,
            'exam_name' => $examSchedule->exam_name ?? '',
            'target_score' => $examSchedule->target_score ?? null
        ];
    } else {
        $step2Data['data'] = [
            'exam_date' => null,
            'd_day' => null,
            'exam_name' => '시험 정보 없음',
            'target_score' => null
        ];
    }

    $collectedData['steps']['step2'] = $step2Data;

    /**
     * Step 3: 목표 분석
     */
    $step3Data = [
        'step' => 3,
        'title' => '목표 분석',
        'data' => null
    ];

    // mdl_alt42g_goal_analysis 테이블에서 최근 데이터 가져오기
    $goalAnalysis = $DB->get_records('alt42g_goal_analysis', ['userid' => $userId], 'created_at DESC', '*', 0, 5);
    if ($goalAnalysis) {
        $step3Data['data'] = array_values($goalAnalysis);
    } else {
        $step3Data['data'] = [];
    }

    $collectedData['steps']['step3'] = $step3Data;

    /**
     * Step 4: 활동 유형 선택
     */
    $step4Data = [
        'step' => 4,
        'title' => '활동 유형',
        'data' => null
    ];

    // localStorage에서 가져올 데이터이므로 기본값 설정
    $step4Data['data'] = [
        'activity_type' => 'problem_solving',
        'note' => 'Client-side data from localStorage'
    ];

    $collectedData['steps']['step4'] = $step4Data;

    /**
     * Step 5: 학습 감정 상태
     */
    $step5Data = [
        'step' => 5,
        'title' => '학습 감정 상태',
        'data' => null
    ];

    // mdl_alt42_emotion_log 또는 유사 테이블에서 가져오기
    $step5Data['data'] = [
        'emotion' => 'neutral',
        'stress_level' => 5,
        'note' => 'Emotion data placeholder'
    ];

    $collectedData['steps']['step5'] = $step5Data;

    /**
     * Step 6: 교사 피드백
     */
    $step6Data = [
        'step' => 6,
        'title' => '교사 피드백',
        'data' => null
    ];

    // mdl_abessi_todayplans에서 최근 피드백 가져오기
    $teacherFeedback = $DB->get_records_sql(
        "SELECT * FROM {abessi_todayplans} WHERE userid = ? ORDER BY timecreated DESC LIMIT 3",
        [$userId]
    );

    if ($teacherFeedback) {
        $step6Data['data'] = array_values($teacherFeedback);
    } else {
        $step6Data['data'] = [];
    }

    $collectedData['steps']['step6'] = $step6Data;

    /**
     * Step 14: 현재 위치 (오답 노트)
     */
    $step14Data = [
        'step' => 14,
        'title' => '현재 위치 (오답 노트)',
        'data' => null
    ];

    // 오답 데이터 수집 (예시)
    $step14Data['data'] = [
        'wrong_answers_count' => 0,
        'weak_concepts' => [],
        'note' => 'Wrong answer data placeholder'
    ];

    $collectedData['steps']['step14'] = $step14Data;

    // 성공 응답
    echo json_encode([
        'success' => true,
        'data' => $collectedData,
        'message' => 'Workflow data collected successfully',
        'file' => 'collect_workflow_data.php',
        'line' => 167
    ]);

} catch (Exception $e) {
    // 에러 응답
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => 'collect_workflow_data.php',
        'line' => __LINE__
    ]);
}

/**
 * Database Tables Used:
 * - mdl_alt42_exam_schedule: 시험 일정 정보
 *   Fields: userid, exam_date, d_day, exam_name, target_score
 * - mdl_alt42g_goal_analysis: 목표 분석 데이터
 *   Fields: userid, analysis_type, result, created_at
 * - mdl_abessi_todayplans: 교사 피드백 데이터
 *   Fields: userid, timecreated, plan1-16, status01-16
 */

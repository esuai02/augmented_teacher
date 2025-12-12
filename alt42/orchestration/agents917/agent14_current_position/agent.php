<?php
/**
 * Agent 14 - Current Position Evaluation
 * File: alt42/orchestration/agents/agent14_current_position/agent.php
 * 역할: 수학일기 기반 현재 위치 평가 및 진행 상태 분석
 * - 계획(tbegin + duration) 대비 실제 완료(tend) 시간 비교
 * - 감정 표현(만족도) 기반 종합 분석
 * - 다른 에이전트에게 전달할 요약 리포트 생성
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : null;

header('Content-Type: application/json; charset=utf-8');

try {
    // 입력 파라미터
    $studentid = isset($_GET['userid']) ? intval($_GET['userid']) : $USER->id;
    if (empty($studentid)) {
        throw new Exception('Student ID is required - File: ' . __FILE__ . ', Line: ' . __LINE__);
    }

    $now = time();
    $twelveHoursAgo = $now - 43200; // 12시간 전

    // 최근 12시간 이내 수학일기 데이터 조회
    $diaryRecord = $DB->get_record_sql(
        "SELECT * FROM {abessi_todayplans}
         WHERE userid = ?
         AND timecreated >= ?
         ORDER BY timecreated DESC
         LIMIT 1",
        array($studentid, $twelveHoursAgo)
    );

    if (!$diaryRecord) {
        echo json_encode(array(
            'success' => true,
            'data' => array(
                'student_id' => $studentid,
                'status' => 'no_data',
                'message' => '최근 12시간 이내 수학일기 데이터가 없습니다.',
                'analysis' => array(),
                'summary' => '일기 데이터 없음'
            )
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }

    // plan1-16 데이터 분석
    $entries = array();
    $totalPlanned = 0;      // 계획된 총 시간(분)
    $totalCompleted = 0;    // 완료된 항목 수
    $delayedItems = 0;      // 지연된 항목 수
    $onTimeItems = 0;       // 적절한 항목 수 (±30분)
    $earlyItems = 0;        // 원활한 항목 수 (예상보다 빠름)

    $satisfactionScores = array(
        '매우만족' => 0,
        '만족' => 0,
        '불만족' => 0
    );

    // 시작 시간 계산 (tbegin 기준, 5분 단위 반올림)
    $baseTime = $diaryRecord->tbegin;
    $minutes = (int)date('i', $baseTime);
    $roundedMinutes = ceil($minutes / 5) * 5;

    if ($roundedMinutes >= 60) {
        $currentTime = strtotime('+1 hour', strtotime(date('Y-m-d H:00:00', $baseTime)));
    } else {
        $currentTime = strtotime(date('Y-m-d H:', $baseTime) . sprintf('%02d', $roundedMinutes) . ':00');
    }

    for ($i = 1; $i <= 16; $i++) {
        $planField = 'plan' . $i;
        $dueField = 'due' . $i;
        $statusField = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT);
        $tendField = 'tend' . str_pad($i, 2, '0', STR_PAD_LEFT);

        $planText = isset($diaryRecord->$planField) ? $diaryRecord->$planField : '';
        $duration = isset($diaryRecord->$dueField) ? intval($diaryRecord->$dueField) : 0;
        $status = isset($diaryRecord->$statusField) ? $diaryRecord->$statusField : '';
        $tend = isset($diaryRecord->$tendField) ? intval($diaryRecord->$tendField) : null;

        if (empty($planText)) {
            continue; // 빈 항목 건너뛰기
        }

        $totalPlanned += $duration;

        // 예상 완료 시간
        $expectedStart = $currentTime;
        $expectedEnd = $expectedStart + ($duration * 60);

        $entryData = array(
            'index' => $i,
            'plan' => $planText,
            'duration_planned' => $duration,
            'expected_start' => $expectedStart,
            'expected_end' => $expectedEnd,
            'status' => $status,
            'tend' => $tend
        );

        // 진행 상태 분석
        if ($tend !== null && $tend > 0) {
            $totalCompleted++;

            // 지연 계산 (분 단위)
            $delay = round(($tend - $expectedEnd) / 60);
            $entryData['actual_completion'] = $tend;
            $entryData['delay_minutes'] = $delay;

            if ($delay > 30) {
                $entryData['progress_status'] = '지연';
                $delayedItems++;
            } elseif ($delay >= -30 && $delay <= 30) {
                $entryData['progress_status'] = '적절';
                $onTimeItems++;
            } else {
                $entryData['progress_status'] = '원활';
                $earlyItems++;
            }

            // 만족도 집계
            if (array_key_exists($status, $satisfactionScores)) {
                $satisfactionScores[$status]++;
            }
        } else {
            $entryData['progress_status'] = '미완료';
            $entryData['actual_completion'] = null;
            $entryData['delay_minutes'] = null;

            // 현재 시간 기준 진행 상태 추정
            if ($now > $expectedEnd) {
                $estimatedDelay = round(($now - $expectedEnd) / 60);
                $entryData['estimated_delay'] = $estimatedDelay;
                if ($estimatedDelay > 30) {
                    $delayedItems++; // 미완료지만 지연으로 간주
                }
            }
        }

        $entries[] = $entryData;
        $currentTime = $expectedEnd; // 다음 항목 시작 시간
    }

    // 종합 분석
    $completionRate = count($entries) > 0 ? round(($totalCompleted / count($entries)) * 100, 1) : 0;

    // 전체 진행 상태 결정
    $overallStatus = '적절';
    if ($delayedItems > $onTimeItems + $earlyItems) {
        $overallStatus = '지연';
    } elseif ($earlyItems > $delayedItems + $onTimeItems) {
        $overallStatus = '원활';
    }

    // 감정 분석
    $emotionalState = '중립';
    $verySatisfied = $satisfactionScores['매우만족'];
    $satisfied = $satisfactionScores['만족'];
    $dissatisfied = $satisfactionScores['불만족'];

    if ($verySatisfied > $satisfied + $dissatisfied) {
        $emotionalState = '매우 긍정';
    } elseif ($satisfied > $verySatisfied + $dissatisfied) {
        $emotionalState = '긍정';
    } elseif ($dissatisfied > $verySatisfied + $satisfied) {
        $emotionalState = '부정';
    }

    // 인사이트 생성
    $insights = array();
    if ($overallStatus === '지연') {
        $insights[] = "현재 {$delayedItems}개 항목이 지연되고 있습니다.";
        $insights[] = "계획 대비 실행 시간 재조정이 필요합니다.";
    } elseif ($overallStatus === '원활') {
        $insights[] = "예상보다 빠르게 진행되고 있습니다 ({$earlyItems}개 항목).";
        $insights[] = "여유 시간을 활용하여 심화 학습을 추천합니다.";
    } else {
        $insights[] = "대체로 계획대로 진행되고 있습니다.";
    }

    if ($emotionalState === '부정') {
        $insights[] = "불만족 응답이 많습니다. 학습 방식 점검이 필요합니다.";
    } elseif ($emotionalState === '매우 긍정') {
        $insights[] = "매우 긍정적인 학습 경험을 하고 있습니다.";
    }

    // 추천 사항
    $recommendations = array();
    if ($delayedItems > 0) {
        $recommendations[] = "지연 항목을 2회로 분할하여 이번 주에 나눠서 해결하세요.";
        $recommendations[] = "각 학습 블록의 집중도를 높이기 위해 10분 단위로 재구성하세요.";
    }

    if ($emotionalState === '부정') {
        $recommendations[] = "어려운 부분은 건너뛰고 쉬운 문제부터 해결하여 자신감을 회복하세요.";
        $recommendations[] = "5분 휴식 후 재시작하거나 학습 방법을 변경해보세요.";
    }

    if (empty($recommendations)) {
        $recommendations[] = "현재 페이스를 유지하며 계획된 목표를 진행하세요.";
    }

    // 다른 에이전트 전달용 요약
    $agentSummary = sprintf(
        "[Agent14 분석] 완료율 %s%% | 진행상태: %s (%s개 지연, %s개 적절, %s개 원활) | 감정상태: %s (%s만족 %s만족 %s불만족) | 권장: %s",
        $completionRate,
        $overallStatus,
        $delayedItems,
        $onTimeItems,
        $earlyItems,
        $emotionalState,
        $verySatisfied,
        $satisfied,
        $dissatisfied,
        $delayedItems > 0 ? '지연항목 분할처리' : '현재 페이스 유지'
    );

    // 최종 응답
    $payload = array(
        'success' => true,
        'data' => array(
            'student_id' => $studentid,
            'diary_id' => $diaryRecord->id,
            'analysis_time' => $now,
            'diary_created' => $diaryRecord->timecreated,
            'overall_status' => $overallStatus,
            'emotional_state' => $emotionalState,
            'completion_rate' => $completionRate,
            'statistics' => array(
                'total_entries' => count($entries),
                'completed' => $totalCompleted,
                'delayed' => $delayedItems,
                'on_time' => $onTimeItems,
                'early' => $earlyItems,
                'total_planned_minutes' => $totalPlanned,
                'satisfaction' => $satisfactionScores
            ),
            'entries' => $entries,
            'insights' => $insights,
            'recommendations' => $recommendations,
            'agent_summary' => $agentSummary
        )
    );

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;

} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ), JSON_UNESCAPED_UNICODE);
    exit;
}
?>

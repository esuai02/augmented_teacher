<?php
/**
 * Agent 12 - Rest Routine Analysis
 * File: agents/agent12_rest_routine/agent.php
 * 휴식 루틴 분석 및 데이터 로드
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data;

header('Content-Type: application/json');

try {
    // 학생 ID 가져오기
    $studentid = $_GET['userid'] ?? $USER->id;

    if (empty($studentid)) {
        throw new Exception('Student ID is required - File: agent.php, Line: ' . __LINE__);
    }

    // 30일간 휴식 데이터 조회
    $sql = "SELECT userid, duration, timecreated
            FROM {abessi_breaktimelog}
            WHERE userid = ?
              AND timecreated >= ?
            ORDER BY timecreated ASC";

    $records = $DB->get_records_sql($sql, [
        $studentid,
        time() - (30 * 86400)  // 30일 전
    ]);

    // 휴식 패턴 분석
    if (empty($records)) {
        // 휴식 미사용형
        $patternType = '휴식 미사용형';
        $avgInterval = 0;
        $restCount = 0;
        $minInterval = 0;
        $maxInterval = 0;
        $consistency = 0;
    } else {
        $restCount = count($records);
        $intervals = [];
        $prevEndTime = null;

        // 휴식 간격 계산
        foreach ($records as $record) {
            if ($prevEndTime !== null) {
                // 이전 휴식 종료 시간부터 다음 휴식 시작까지의 간격 (분 단위)
                $interval = ($record->timecreated - $prevEndTime) / 60;
                $intervals[] = $interval;
            }
            // 현재 휴식의 종료 시간
            $prevEndTime = $record->timecreated + $record->duration;
        }

        // 통계 계산
        if (!empty($intervals)) {
            $avgInterval = array_sum($intervals) / count($intervals);
            $minInterval = min($intervals);
            $maxInterval = max($intervals);

            // 일관성 점수 계산 (표준편차 기반)
            $variance = 0;
            foreach ($intervals as $interval) {
                $variance += pow($interval - $avgInterval, 2);
            }
            $stdDev = sqrt($variance / count($intervals));
            // 일관성 점수: 표준편차가 작을수록 높음 (0-100)
            $consistency = max(0, 100 - ($stdDev / $avgInterval * 100));
        } else {
            $avgInterval = 0;
            $minInterval = 0;
            $maxInterval = 0;
            $consistency = 0;
        }

        // 패턴 유형 분류
        if ($avgInterval <= 60) {
            $patternType = '정기적 휴식형';
        } elseif ($avgInterval <= 90) {
            $patternType = '활동 중심 휴식형';
        } else {
            $patternType = '집중 몰입형';
        }
    }

    // 인사이트 생성
    $insights = [];
    $insights[] = "현재 휴식 패턴: {$patternType}";

    if ($restCount > 0) {
        $insights[] = "최근 30일간 {$restCount}회의 휴식을 취했습니다.";

        if ($avgInterval > 0) {
            $insights[] = "평균 휴식 간격: " . round($avgInterval, 1) . "분";
        }

        if ($consistency > 0) {
            $consistencyLevel = $consistency >= 70 ? '높음' : ($consistency >= 50 ? '보통' : '낮음');
            $insights[] = "휴식 패턴 일관성: {$consistencyLevel} (" . round($consistency, 1) . "점)";
        }
    } else {
        $insights[] = "최근 30일간 휴식 기록이 없습니다.";
    }

    // 코칭 템플릿 선택
    $recommendations = [];

    switch($patternType) {
        case '정기적 휴식형':
            $recommendations[] = '⚠️ 휴식을 너무 규칙적으로 시행하는 경향이 있습니다.';
            $recommendations[] = '💡 학습 중인 내용을 중간에 중단하는 습관을 주의하세요.';
            $recommendations[] = '🎯 활동 완료 후 휴식을 취하는 유연한 패턴을 시도해보세요.';
            break;

        case '활동 중심 휴식형':
            $recommendations[] = '✅ 균형 잡힌 휴식 패턴을 가지고 있습니다.';
            $recommendations[] = '👍 현재 활동을 완료하고 휴식하는 좋은 습관입니다.';
            $recommendations[] = '💪 이 패턴을 유지하세요!';
            break;

        case '집중 몰입형':
            $recommendations[] = '⚠️ 휴식 간격이 매우 깁니다 (90분 이상).';
            $recommendations[] = '💡 장기적인 학습 슬럼프 가능성을 체크하세요.';
            $recommendations[] = '🌟 컨디션 관리를 위해 중간 휴식을 고려해보세요.';
            break;

        case '휴식 미사용형':
            $recommendations[] = '🚨 정기적인 휴식 루틴이 없어 보입니다.';
            $recommendations[] = '💡 좋은 컨디션과 기분 속에서 공부할 수 있도록 간단한 자신만의 휴식 규칙을 만들어 보세요.';
            $recommendations[] = '💧 "물 마시고 눈 휴식 1분 후 이어갑시다."';
            $recommendations[] = '🔔 휴식과 공부가 구분이 없으면 컨디션이 저하된 상태에서 공부하는 구간이 생깁니다.';
            break;
    }

    // 지식파일 로드
    $knowledgePath = __DIR__ . '/agent12_rest_routine.md';
    $knowledgeText = file_exists($knowledgePath) ? file_get_contents($knowledgePath) : '';

    // 응답 구성
    $response = [
        'success' => true,
        'data' => [
            'student_id' => $studentid,
            'pattern_type' => $patternType,
            'rest_count' => $restCount,
            'avg_interval_minutes' => round($avgInterval, 1),
            'min_interval' => round($minInterval, 1),
            'max_interval' => round($maxInterval, 1),
            'consistency_score' => round($consistency, 1),
            'statistics' => [
                'count' => $restCount,
                'intervals' => count($intervals),
                'avg' => round($avgInterval, 1),
                'min' => round($minInterval, 1),
                'max' => round($maxInterval, 1),
                'consistency' => round($consistency, 1)
            ],
            'insights' => $insights,
            'recommendations' => $recommendations
        ],
        'knowledge' => $knowledgeText,
        'message' => '휴식 루틴 분석이 완료되었습니다.'
    ];

} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => 'Error in agent.php line ' . __LINE__ . ': ' . $e->getMessage()
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

<?php
/**
 * Agent 08 - Calmness Analysis
 * File: agents/agent08_calmness/agent.php
 * 침착도 분석 및 데이터 로드
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 학생 ID 가져오기
$studentid = $_GET["userid"] ?? $USER->id;

try {
    // 현재 침착도 데이터 (최근 12시간)
    $sql = "SELECT * FROM {abessi_today}
            WHERE userid = ?
            AND (type = ? OR type = ?)
            AND timecreated > ?
            ORDER BY id DESC
            LIMIT 1";

    $current = $DB->get_record_sql($sql, [
        $studentid,
        '오늘목표',
        '검사요청',
        time() - 43200
    ]);

    // 주간 기준선
    $sql = "SELECT * FROM {abessi_today}
            WHERE userid = ?
            AND type = ?
            ORDER BY id DESC
            LIMIT 1";

    $baseline = $DB->get_record_sql($sql, [
        $studentid,
        '주간목표'
    ]);

    // 최근 7일 데이터
    $sql = "SELECT * FROM {abessi_today}
            WHERE userid = ?
            AND (type = ? OR type = ?)
            AND timecreated >= ?
            ORDER BY timecreated DESC
            LIMIT 30";

    $records = $DB->get_records_sql($sql, [
        $studentid,
        '오늘목표',
        '검사요청',
        time() - (7 * 86400)
    ]);

    // 점수 분석
    $currentScore = $current ? floatval($current->score) : null;
    $baselineScore = $baseline ? floatval($baseline->score) : null;

    // 레벨 판정
    $level = '데이터 없음';
    if ($currentScore !== null) {
        if ($currentScore >= 95) $level = '매우 침착';
        elseif ($currentScore >= 90) $level = '침착';
        elseif ($currentScore >= 85) $level = '침착한 편';
        elseif ($currentScore >= 80) $level = '침착도 보통';
        elseif ($currentScore >= 75) $level = '침착도 약함';
        elseif ($currentScore >= 70) $level = '침착도 위험';
        else $level = '침착도 긴급복구필요';
    }

    // 추이 분석
    $trend = '비교 데이터 부족';
    $trendEmoji = '➡️';
    $delta = 0;
    if ($currentScore !== null && $baselineScore !== null) {
        $delta = $currentScore - $baselineScore;
        if ($delta >= 5) {
            $trend = '평소보다 침착도가 높음';
            $trendEmoji = '📈';
        } elseif ($delta <= -5) {
            $trend = '평소보다 침착도가 낮음';
            $trendEmoji = '📉';
        } else {
            $trend = '평소와 비슷';
            $trendEmoji = '➡️';
        }
    }

    // 통계 계산
    $scores = [];
    foreach ($records as $record) {
        $scores[] = floatval($record->score ?? 0);
    }

    $average = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0;
    $min = count($scores) > 0 ? min($scores) : 0;
    $max = count($scores) > 0 ? max($scores) : 0;

    // 인사이트 생성
    $insights = [];
    if ($currentScore !== null) {
        $insights[] = "현재 침착도가 {$currentScore}점으로 '{$level}' 상태입니다.";
    }
    if ($trend !== '비교 데이터 부족') {
        $insights[] = "{$trendEmoji} 주간 평균 대비: {$trend}";
    }
    if (count($scores) > 0) {
        $insights[] = "분석 기간 평균: {$average}점 (최저 {$min}점 ~ 최고 {$max}점)";
    }

    // 추천사항 생성
    $recommendations = [];
    if ($currentScore >= 90) {
        $recommendations[] = "✅ 우수한 침착도를 유지하고 있습니다.";
        $recommendations[] = "🎯 도전적인 학습에 적합한 상태입니다.";
    } elseif ($currentScore >= 80) {
        $recommendations[] = "👍 안정적인 학습 상태입니다.";
        $recommendations[] = "💡 꾸준한 학습을 지속하세요.";
    } elseif ($currentScore >= 70) {
        $recommendations[] = "⚠️ 침착도가 다소 낮습니다. 짧은 휴식을 권장합니다.";
    } else {
        $recommendations[] = "🚨 침착도 회복이 필요합니다. 충분한 휴식을 취하세요.";
    }

    // 지식파일 로드
    $knowledgePath = __DIR__ . '/agent08_calmness.md';
    $knowledgeText = file_exists($knowledgePath) ? file_get_contents($knowledgePath) : '';

    $response = [
        'success' => true,
        'data' => [
            'student_id' => $studentid,
            'current_score' => $currentScore,
            'current_level' => $level,
            'baseline_score' => $baselineScore,
            'trend' => $trend,
            'trend_emoji' => $trendEmoji,
            'delta' => $delta,
            'statistics' => [
                'count' => count($scores),
                'average' => $average,
                'min' => $min,
                'max' => $max
            ],
            'insights' => $insights,
            'recommendations' => $recommendations
        ],
        'knowledge' => $knowledgeText,
        'message' => '침착도 분석이 완료되었습니다.'
    ];

} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => 'Error in agent.php line ' . __LINE__ . ': ' . $e->getMessage()
    ];
}

header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE);

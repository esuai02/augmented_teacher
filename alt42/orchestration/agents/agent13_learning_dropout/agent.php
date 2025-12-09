<?php
/**
 * Agent 13 - Learning Dropout Analysis (24h rolling)
 * File: agents/agent13_learning_dropout/agent.php
 * 역할: 최근 24시간 내 학습 이탈 신호를 집계하여 JSON으로 반환
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할 확인(운영 정책 준수)
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : null;

header('Content-Type: application/json');

try {
    // 입력 파라미터
    $studentid = isset($_GET['userid']) ? $_GET['userid'] : $USER->id;
    if (empty($studentid)) {
        throw new Exception('Student ID is required - File: agents/agent13_learning_dropout/agent.php, Line: ' . __LINE__);
    }

    $now = time();
    $windowStart = $now - 86400; // 24h rolling

    // 1) abessi_today: 최근 목표/검사 행(24h 내)
    $goalSql = "SELECT id, userid, ninactive, nlazy, activetime, checktime, status, type, timecreated, timemodified
                FROM {abessi_today}
                WHERE userid = ?
                  AND timecreated >= ?
                  AND (type = '오늘목표' OR type = '검사요청')
                ORDER BY id DESC
                LIMIT 1";
    $goal = $DB->get_record_sql($goalSql, [$studentid, $windowStart]);

    // 2) abessi_messages: 최근 보드/노트 활동(24h 내)
    $msgSql = "SELECT timemodified, tlaststroke
               FROM {abessi_messages}
               WHERE userid = ?
                 AND timemodified >= ?
               ORDER BY tlaststroke DESC
               LIMIT 1";
    $msg = $DB->get_record_sql($msgSql, [$studentid, $windowStart]);

    // 3) abessi_tracking: 최근 타임스캐폴딩(가장 최신)
    $trkSql = "SELECT status, timecreated, duration, text
               FROM {abessi_tracking}
               WHERE userid = ?
               ORDER BY id DESC
               LIMIT 1";
    $trk = $DB->get_record_sql($trkSql, [$studentid]);

    // 4) abessi_indicators: 포모도로 요약(가장 최신)
    $indSql = "SELECT npomodoro, kpomodoro, pmresult, nalt, timecreated
               FROM {abessi_indicators}
               WHERE userid = ?
               ORDER BY id DESC
               LIMIT 1";
    $ind = $DB->get_record_sql($indSql, [$studentid]);

    // === 지표 계산 ===
    $ninactive = $goal ? (int)$goal->ninactive : 0;
    $nlazyBlocks = $goal ? (int)round(((int)$goal->nlazy) / 20, 0) : 0; // 20분당 1 블록

    $timespentMin = null; // 노트 지연 시청 시간(분)
    if ($msg && isset($msg->timemodified)) {
        $timespentMin = (int)round(($now - (int)$msg->timemodified) / 60, 0);
    }
    $eyeFlag = ($timespentMin !== null && $timespentMin >= 5);

    // tlaststroke 계산: min(messages.tlaststroke, goal.timecreated, tracking.timecreated)
    $cands = [];
    if ($msg && isset($msg->tlaststroke) && (int)$msg->tlaststroke > 0) $cands[] = (int)$msg->tlaststroke;
    if ($goal && isset($goal->timecreated) && (int)$goal->timecreated > 0) $cands[] = (int)$goal->timecreated;
    if ($trk && isset($trk->timecreated) && (int)$trk->timecreated > 0) $cands[] = (int)$trk->timecreated;
    $tlaststrokeMin = null;
    if (!empty($cands)) {
        $tlaststrokeSec = $now - min($cands);
        $tlaststrokeMin = (int)round($tlaststrokeSec / 60, 0);
    }

    $npomodoro = $ind ? (int)$ind->npomodoro : 0;
    $kpomodoro = $ind ? (int)$ind->kpomodoro : 0;
    $pmresult  = $ind ? (int)$ind->pmresult  : 0;

    // 위험 등급 산정(문서 기준)
    $riskTier = 'low';
    if (($ninactive >= 4) || ($npomodoro < 2) || ($tlaststrokeMin !== null && $tlaststrokeMin >= 30)) {
        $riskTier = 'high';
    } elseif (($ninactive >= 2 && $ninactive <= 3) || ($npomodoro >= 2 && $npomodoro <= 4) || ($eyeFlag)) {
        $riskTier = 'medium';
    }

    // 인사이트 & 추천(간단 규칙)
    $insights = [];
    $recs = [];
    if ($eyeFlag) {
        $insights[] = '최근 노트를 5분 이상 지연 시청 중으로 추정됩니다.';
        $recs[] = '10분 타이머로 즉시 미세목표를 시작하세요.';
    }
    if ($ninactive >= 1) {
        $insights[] = '이탈 경고가 누적되어 있습니다.';
        $recs[] = '쉬운 승리 과제를 먼저 완료하고 리듬을 회복하세요.';
    }
    if ($npomodoro < 5) {
        $insights[] = '포모도로 루틴이 약합니다(npomodoro<5).';
        $recs[] = '오늘 10~15분 세션 2회 목표를 설정하세요.';
    }

    if (empty($insights)) $insights[] = '24시간 기준으로 특이 신호 없음(정상 범주).';
    if (empty($recs)) $recs[] = '현재 루틴을 유지하고 계획된 목표를 진행하세요.';

    $payload = [
        'success' => true,
        'data' => [
            'student_id' => (int)$studentid,
            'window' => [ 'from' => $windowStart, 'to' => $now ],
            'risk_tier' => $riskTier,
            'metrics' => [
                'ninactive' => $ninactive,
                'nlazy_blocks' => $nlazyBlocks,
                'eye_flag' => (bool)$eyeFlag,
                'eye_timespent_min' => $timespentMin,
                'tlaststroke_min' => $tlaststrokeMin,
                'npomodoro' => $npomodoro,
                'kpomodoro' => $kpomodoro,
                'pmresult' => $pmresult
            ],
            'insights' => $insights,
            'recommendations' => $recs
        ]
    ];

    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage() . ' - File: agents/agent13_learning_dropout/agent.php, Line: ' . __LINE__
    ], JSON_UNESCAPED_UNICODE);
    exit;
}



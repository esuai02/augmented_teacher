<?php
/**
 * Agent 11 - Problem Notes Analysis
 * File: agents/agent11_problem_notes/agent.php
 *
 * 요구사항:
 * - 테이블: mdl_abessi_messages
 * - 조건: contentstype=2, userid = 현재 학생
 * - 3개 탭으로 분류:
 *   1. 풀이노트: status='begin'
 *   2. 준비노트: status='incorrect'
 *   3. 서술평가: status IN ('exam', 'complete', 'review')
 * - 출력 필드: nstroke(총필기양), tlaststroke(마지막 필기 unixtime), timecreated(생성 unixtime),
 *              contentstitle(문제 제목), url(books/mynote.php? 뒤에 붙는 쿼리), usedtime(소요시간), status
 * - 기본 조회 기간: 최근 1주일
 * - 1주 단위 페이지네이션 (week_offset 파라미터, 0이 이번 주, -1이 이전 주 등)
 * - 에러 발생 시 파일 경로와 라인 포함
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할 조회 (환경 규칙 준수)
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid = ? AND fieldid = '22'", [$USER->id]);
$role = $userrole && isset($userrole->data) ? $userrole->data : '';

header('Content-Type: application/json');

try {
    // 학생 ID: 쿼리스트링으로 받되 없으면 현재 로그인 사용자
    $studentid = isset($_GET['userid']) && $_GET['userid'] !== '' ? $_GET['userid'] : $USER->id;

    // 주 단위 이동: 기본 0(이번 주). -1: 직전 주, +1: 다음 주(미래 주는 보통 데이터 없음)
    $weekOffset = isset($_GET['week_offset']) ? intval($_GET['week_offset']) : 0;

    // 한 주(7일) 초 단위
    $oneWeekSeconds = 7 * 24 * 60 * 60;
    // 기준: 현재 시각을 끝점으로 최근 1주. offset이 음수면 과거 주로 이동
    $now = time();
    $endTime = $now - ($oneWeekSeconds * $weekOffset);
    $startTime = $endTime - $oneWeekSeconds;

    // contentstype=2 (문제풀이 노트)
    // 기본 쿼리: 주어진 기간, contentstype=2, userid 조건
    $baseSql = "SELECT id, userid, nstroke, tlaststroke, timecreated, contentstitle, wboardid, usedtime, status
                FROM {abessi_messages}
                WHERE contentstype = ?
                  AND userid = ?
                  AND timecreated >= ?
                  AND timecreated < ?";

    // 1. 풀이노트 (status='attempt')
    $sqlAttempt = $baseSql . " AND status = ? ORDER BY timecreated DESC";
    $recordsAttempt = $DB->get_records_sql($sqlAttempt, [2, $studentid, $startTime, $endTime, 'attempt']);

    // 2. 준비노트 (status='begin')
    $sqlBegin = $baseSql . " AND status = ? ORDER BY timecreated DESC";
    $recordsBegin = $DB->get_records_sql($sqlBegin, [2, $studentid, $startTime, $endTime, 'begin']);

    // 3. 서술평가 (status IN ('exam', 'complete', 'review'))
    $sqlEssay = $baseSql . " AND status IN (?, ?, ?) ORDER BY timecreated DESC";
    $recordsEssay = $DB->get_records_sql($sqlEssay, [2, $studentid, $startTime, $endTime, 'exam', 'complete', 'review']);

    // 데이터 변환 헬퍼 함수
    function formatRecords($records) {
        $rows = [];
        $baseUrl = 'https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id=';
        foreach ($records as $rec) {
            $rows[] = [
                'nstroke' => isset($rec->nstroke) ? intval($rec->nstroke) : 0,
                'tlaststroke' => isset($rec->tlaststroke) ? intval($rec->tlaststroke) : null,
                'timecreated' => isset($rec->timecreated) ? intval($rec->timecreated) : null,
                'contentstitle' => isset($rec->contentstitle) ? (string)$rec->contentstitle : '',
                'url' => isset($rec->wboardid) && $rec->wboardid ? ($baseUrl . $rec->wboardid) : '',
                'usedtime' => isset($rec->usedtime) ? intval($rec->usedtime) : 0,
                'status' => isset($rec->status) ? (string)$rec->status : '',
            ];
        }
        return $rows;
    }

    $rowsAttempt = formatRecords($recordsAttempt);
    $rowsBegin = formatRecords($recordsBegin);
    $rowsEssay = formatRecords($recordsEssay);

    // 페이지네이션 정보
    $prevStart = $startTime - $oneWeekSeconds;
    $prevEnd = $startTime;
    $nextStart = $endTime;
    $nextEnd = $endTime + $oneWeekSeconds;

    $prevCount = $DB->count_records_sql(
        "SELECT COUNT(1) FROM {abessi_messages} WHERE contentstype=? AND userid=? AND timecreated>=? AND timecreated<?",
        [2, $studentid, $prevStart, $prevEnd]
    );
    $nextCount = $DB->count_records_sql(
        "SELECT COUNT(1) FROM {abessi_messages} WHERE contentstype=? AND userid=? AND timecreated>=? AND timecreated<?",
        [2, $studentid, $nextStart, $nextEnd]
    );

    // 가상 분석 결과
    $totalAttempt = count($rowsAttempt);
    $totalBegin = count($rowsBegin);
    $totalEssay = count($rowsEssay);

    $analysisText = "최근 1주 문제노트 분석: ";
    $analysisText .= "풀이노트 {$totalAttempt}개, 준비노트 {$totalBegin}개, 서술평가 {$totalEssay}개가 작성되었습니다. ";
    $analysisText .= "오답노트 작성 습관을 통해 취약 영역을 파악하고 복습 전략을 수립하세요.";

    // 지식파일 로드
    $knowledgePath = __DIR__ . '/agent11_problem_notes.md';
    $knowledgeText = file_exists($knowledgePath) ? file_get_contents($knowledgePath) : '';

    echo json_encode([
        'success' => true,
        'data' => [
            'student_id' => $studentid,
            'week_offset' => $weekOffset,
            'period' => [
                'start' => $startTime,
                'end' => $endTime
            ],
            'tabs' => [
                'attempt' => [
                    'title' => '풀이노트',
                    'rows' => $rowsAttempt,
                    'count' => $totalAttempt
                ],
                'begin' => [
                    'title' => '준비노트',
                    'rows' => $rowsBegin,
                    'count' => $totalBegin
                ],
                'essay' => [
                    'title' => '서술평가',
                    'rows' => $rowsEssay,
                    'count' => $totalEssay
                ]
            ],
            'pagination' => [
                'has_prev' => $prevCount > 0,
                'has_next' => $nextCount > 0
            ],
            'analysis_text' => $analysisText,
            'knowledge' => $knowledgeText
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error in agents/agent11_problem_notes/agent.php line ' . __LINE__ . ': ' . $e->getMessage(),
        'file' => 'agents/agent11_problem_notes/agent.php',
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

<?php
/**
 * Agent 10 - Concept Notes Analysis
 * File: agents/agent10_concept_notes/agent.php
 *
 * 요구사항:
 * - 테이블: mdl_abessi_messages
 * - 조건: contentstype=1, userid = 현재 학생
 * - 출력 필드: nstroke(총필기양), tlaststroke(마지막 필기 unixtime), timecreated(생성 unixtime), contentstitle(개념 제목), url(books/mynote.php? 뒤에 붙는 쿼리)
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

    // contentstype=1 (개념공부 화이트보드, 필기)
    // url은 mynote.php? 뒤에 붙는 쿼리 값을 저장하는 것으로 확인됨
    // 주어진 기간에 해당하는 레코드만 조회, 최신순
    $sql = "SELECT id, userid, nstroke, tlaststroke, timecreated, contentstitle, url, usedtime
            FROM {abessi_messages}
            WHERE contentstype = ?
              AND userid = ?
              AND timecreated >= ?
              AND timecreated < ?
            ORDER BY timecreated DESC";

    $records = $DB->get_records_sql($sql, [1, $studentid, $startTime, $endTime]);

    // 표 렌더링용 데이터 구성
    $rows = [];
    $baseUrl = 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?';
    foreach ($records as $rec) {
        $rows[] = [
            'nstroke' => isset($rec->nstroke) ? intval($rec->nstroke) : 0,
            'tlaststroke' => isset($rec->tlaststroke) ? intval($rec->tlaststroke) : null,
            'timecreated' => isset($rec->timecreated) ? intval($rec->timecreated) : null,
            'contentstitle' => isset($rec->contentstitle) ? (string)$rec->contentstitle : '',
            'url' => $baseUrl . (isset($rec->url) ? $rec->url : ''),
            'usedtime' => isset($rec->usedtime) ? intval($rec->usedtime) : 0,
        ];
    }

    // 페이지네이션 정보
    // 이전 주/다음 주 존재 여부 판단을 위해 간단히 해당 구간에 데이터가 있는지 추가 조회
    $prevStart = $startTime - $oneWeekSeconds;
    $prevEnd = $startTime;
    $nextStart = $endTime;
    $nextEnd = $endTime + $oneWeekSeconds;

    $prevCount = $DB->count_records_sql(
        "SELECT COUNT(1) FROM {abessi_messages} WHERE contentstype=? AND userid=? AND timecreated>=? AND timecreated<?",
        [1, $studentid, $prevStart, $prevEnd]
    );
    $nextCount = $DB->count_records_sql(
        "SELECT COUNT(1) FROM {abessi_messages} WHERE contentstype=? AND userid=? AND timecreated>=? AND timecreated<?",
        [1, $studentid, $nextStart, $nextEnd]
    );

    // 가상 분석 결과 (요구사항: 현재는 가상 텍스트)
    $analysisText = "최근 1주 개념노트 필기량과 마지막 필기 시점을 기반으로 학습 활동을 요약합니다. "
        . "상대적으로 필기가 많은 단원은 집중도가 높았을 가능성이 높습니다. 다음 주에는 필기량이 낮았던 단원을 우선 복습해 보세요.";

    // 지식파일 로드 (있으면 첨부)
    $knowledgePath = __DIR__ . '/agent10_concept_notes.md';
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
            'rows' => $rows,
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
        'error' => 'Error in agents/agent10_concept_notes/agent.php line ' . __LINE__ . ': ' . $e->getMessage(),
        'file' => 'agents/agent10_concept_notes/agent.php',
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}



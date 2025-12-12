<?php
/**
 * Math Diary API - Period-based filtering for mdl_abessi_todayplans
 *
 * 수학일기 데이터를 기간별로 조회하는 API
 * Table: mdl_abessi_todayplans
 * Fields: plan1-16, due1-16, url1-16, status01-16
 *
 * @file get_math_diary.php:1
 */

// Moodle 통합
$moodle_config_path = '/home/moodle/public_html/moodle/config.php';
if (!file_exists($moodle_config_path)) {
    error_log("[get_math_diary.php:12] Moodle config not found: {$moodle_config_path}");
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Moodle configuration not found']);
    exit;
}

include_once($moodle_config_path);
global $DB, $USER;

// 로그인 체크
try {
    require_login();
} catch (Exception $e) {
    error_log("[get_math_diary.php:24] Login required: " . $e->getMessage());
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Login required']);
    exit;
}

// 한국 시간대 설정
date_default_timezone_set('Asia/Seoul');

// JSON 응답 헤더
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 입력 데이터 파싱
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = array_merge($_GET, $_POST);
}

$action = isset($input['action']) ? $input['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
$period = isset($input['period']) ? $input['period'] : (isset($_GET['period']) ? $_GET['period'] : 'today');
$user_id = isset($input['user_id']) ? intval($input['user_id']) : (isset($_GET['userid']) ? intval($_GET['userid']) : $USER->id);

// 사용자 ID 검증
if (!$user_id || $user_id <= 0) {
    error_log("[get_math_diary.php:60] Invalid user_id: {$user_id}");
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

/**
 * 기간 계산 함수
 * comprehensive_feedback.php (lines 90-126) 로직 참조
 *
 * @param string $period 기간 문자열
 * @return array [timestamp_from, timestamp_to]
 */
function calculate_period_range($period) {
    switch ($period) {
        case 'today':
            $date_from = date('Y-m-d 00:00:00');
            $date_to = date('Y-m-d 23:59:59');
            break;
        case 'week':
            $date_from = date('Y-m-d 00:00:00', strtotime('-7 days'));
            $date_to = date('Y-m-d 23:59:59');
            break;
        case '2weeks':
            $date_from = date('Y-m-d 00:00:00', strtotime('-14 days'));
            $date_to = date('Y-m-d 23:59:59');
            break;
        case '3weeks':
            $date_from = date('Y-m-d 00:00:00', strtotime('-21 days'));
            $date_to = date('Y-m-d 23:59:59');
            break;
        case '4weeks':
            $date_from = date('Y-m-d 00:00:00', strtotime('-28 days'));
            $date_to = date('Y-m-d 23:59:59');
            break;
        case '3months':
            $date_from = date('Y-m-d 00:00:00', strtotime('-3 months'));
            $date_to = date('Y-m-d 23:59:59');
            break;
        default:
            error_log("[get_math_diary.php:104] Invalid period: {$period}");
            $date_from = date('Y-m-d 00:00:00');
            $date_to = date('Y-m-d 23:59:59');
            break;
    }

    return array(
        'timestamp_from' => strtotime($date_from),
        'timestamp_to' => strtotime($date_to),
        'date_from' => $date_from,
        'date_to' => $date_to
    );
}

/**
 * 수학일기 데이터 파싱 함수 (fback 필드 포함)
 * goals42.php (lines 149-172) 로직 참조
 *
 * fback01~fback16: 포모도로 세션별 선생님/AI 자동생성 피드백
 *
 * @param object $diaryRecord DB 레코드
 * @return array 파싱된 일기 항목 배열
 */
function parse_diary_plans($diaryRecord) {
    $diaryPlans = array();

    for ($i = 1; $i <= 16; $i++) {
        $planField = 'plan' . $i;
        $dueField = 'due' . $i;
        $urlField = 'url' . $i;
        $statusField = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT);
        $fbackField = 'fback' . str_pad($i, 2, '0', STR_PAD_LEFT); // fback01, fback02, ...

        $planText = isset($diaryRecord->$planField) ? trim($diaryRecord->$planField) : '';
        $dueMinutes = isset($diaryRecord->$dueField) ? intval($diaryRecord->$dueField) : 0;
        $urlText = isset($diaryRecord->$urlField) ? trim($diaryRecord->$urlField) : '';
        $statusText = isset($diaryRecord->$statusField) ? trim($diaryRecord->$statusField) : '';
        $feedbackText = isset($diaryRecord->$fbackField) ? trim($diaryRecord->$fbackField) : '';

        // 빈 항목 제외
        if (!empty($planText)) {
            $diaryPlans[] = array(
                'index' => $i,
                'plan' => $planText,
                'duration' => $dueMinutes,
                'url' => $urlText,
                'status' => $statusText,
                'feedback' => $feedbackText  // 포모도로 세션별 피드백 추가
            );
        }
    }

    return $diaryPlans;
}

/**
 * 메모장 데이터 조회 함수 (mdl_abessi_stickynotes)
 *
 * @param int $user_id 사용자 ID
 * @param int $timestamp_from 시작 시간
 * @param int $timestamp_to 종료 시간
 * @return array 메모장 항목 배열
 */
function get_stickynotes($user_id, $timestamp_from, $timestamp_to) {
    global $DB;

    // teacher_feedback_stickynotes.php (lines 179-213) 참조
    $memo_types = array('timescaffolding', 'chapter', 'edittoday', 'mystudy', 'today');

    $sql = "SELECT
                id,
                userid,
                type,
                content,
                created_at,
                updated_at
            FROM {abessi_stickynotes}
            WHERE userid = :userid
            AND type IN ('" . implode("','", $memo_types) . "')
            ORDER BY id DESC";

    $params = array('userid' => $user_id);

    try {
        $results = $DB->get_records_sql($sql, $params);

        $stickyNotes = array();
        $type_labels = array(
            'timescaffolding' => '포모도로',
            'chapter' => '컨텐츠 페이지',
            'edittoday' => '목표설정',
            'mystudy' => '내공부방',
            'today' => '공부결과'
        );

        foreach ($results as $row) {
            $created_time = $row->created_at;

            // 날짜 파싱 (teacher_feedback_stickynotes.php lines 221-244)
            if (strpos($created_time, '-') !== false || strpos($created_time, ':') !== false) {
                $datetime = new DateTime($created_time);
                $memo_timestamp = $datetime->getTimestamp();
            } else if (is_numeric($created_time) && strlen($created_time) == 10) {
                $memo_timestamp = intval($created_time);
            } else {
                $memo_timestamp = time();
            }

            // 기간 필터링
            if ($memo_timestamp < $timestamp_from || $memo_timestamp > $timestamp_to) {
                continue;
            }

            $stickyNotes[] = array(
                'id' => $row->id,
                'type' => $row->type,
                'type_label' => isset($type_labels[$row->type]) ? $type_labels[$row->type] : $row->type,
                'content' => $row->content,
                'created_at' => $created_time,
                'timestamp' => $memo_timestamp,
                'date' => date('Y-m-d', $memo_timestamp),
                'time' => date('H:i:s', $memo_timestamp)
            );
        }

        return $stickyNotes;

    } catch (Exception $e) {
        error_log("[get_math_diary.php:218] Stickynotes query error: " . $e->getMessage());
        return array();
    }
}

// 메인 처리
try {
    if ($action === 'getMathDiary') {
        // 기간 범위 계산
        $period_range = calculate_period_range($period);
        $timestamp_from = $period_range['timestamp_from'];
        $timestamp_to = $period_range['timestamp_to'];

        $debug_info = array(
            'user_id' => $user_id,
            'period' => $period,
            'date_from' => $period_range['date_from'],
            'date_to' => $period_range['date_to'],
            'timestamp_from' => $timestamp_from,
            'timestamp_to' => $timestamp_to
        );

        // 1. 수학일기 조회 (mdl_abessi_todayplans)
        // goals42.php (lines 137-147) 참조, 12시간 필터를 기간 필터로 변경
        $sql = "SELECT * FROM {abessi_todayplans}
                WHERE userid = :userid
                AND timecreated >= :timestamp_from
                AND timecreated <= :timestamp_to
                ORDER BY timecreated DESC";

        $params = array(
            'userid' => $user_id,
            'timestamp_from' => $timestamp_from,
            'timestamp_to' => $timestamp_to
        );

        $diaryRecords = $DB->get_records_sql($sql, $params);

        $debug_info['query_executed'] = true;
        $debug_info['total_diary_records'] = count($diaryRecords);

        // 각 레코드 파싱 (fback01~fback16 포함)
        $allDiaryPlans = array();
        foreach ($diaryRecords as $record) {
            $plans = parse_diary_plans($record);
            if (!empty($plans)) {
                $allDiaryPlans[] = array(
                    'timecreated' => $record->timecreated,
                    'date' => date('Y-m-d', $record->timecreated),
                    'time' => date('H:i:s', $record->timecreated),
                    'plans' => $plans
                );
            }
        }

        $debug_info['total_plans'] = count($allDiaryPlans);

        // 2. 메모장 데이터 조회 (mdl_abessi_stickynotes)
        $stickyNotes = get_stickynotes($user_id, $timestamp_from, $timestamp_to);
        $debug_info['total_stickynotes'] = count($stickyNotes);

        // 응답 생성
        echo json_encode(array(
            'success' => true,
            'period' => $period,
            'date_from' => $period_range['date_from'],
            'date_to' => $period_range['date_to'],
            'diary_entries' => $allDiaryPlans,  // 수학일기 (fback 포함)
            'sticky_notes' => $stickyNotes,     // 메모장 전달 내용
            'total_diary_count' => count($allDiaryPlans),
            'total_notes_count' => count($stickyNotes),
            'debug' => $debug_info
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    } else {
        error_log("[get_math_diary.php:227] Invalid action: {$action}");
        http_response_code(400);
        echo json_encode(array(
            'success' => false,
            'error' => '올바르지 않은 요청입니다',
            'received_action' => $action
        ));
    }

} catch (Exception $e) {
    error_log("[get_math_diary.php:237] Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
        'file' => 'get_math_diary.php',
        'line' => $e->getLine()
    ));
}
?>
